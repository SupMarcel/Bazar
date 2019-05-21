<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 18:42
 */

namespace App\Model;

use Latte;
use Nette;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;

class OfferManager extends BaseManager
{
    const
        TABLE_NAME = 'nabidka',
        COLUMN_ID = 'id',
        COLUMN_USER = 'uzivatel',
        COLUMN_TITLE = 'nazevZbozi',
        COLUMN_PRICE = 'cena',
        COLUMN_DESCRIPTION = 'popisZbozi',
        COLUMN_CATEGORY='kategorie',
        COLUMN_MAIN_PHOTO='hlavniFotografie';

    /** @var  CategoryManager */
    private $categoryManager;
    /** @var  UserManager */
    private $userManager;

    /** @var  CommentManager */
    private $commentManager;

    /** @var  PhotoManager */
    private $photoManager;

    /** @var  Sender */
    private $sender;

    public function __construct(Nette\Database\Context $database, CategoryManager $categoryManager,
                                UserManager $userManager,
        CommentManager $commentManager, PhotoManager $photoManager, Sender $sender)
    {
        parent::__construct($database);
        $this->categoryManager = $categoryManager;
        $this->userManager = $userManager;
        $this->commentManager = $commentManager;
        $this->photoManager = $photoManager;
        $this->sender = $sender;
    }

    public function addOffer($properties){
        $count = $this->database->table(self::TABLE_NAME)->count(self::COLUMN_ID);
        $maxID = $this->database->table(self::TABLE_NAME)->max(self::COLUMN_ID);
        $max = $count == 0 ? 1 : $maxID + 1;
        $array = [self::COLUMN_ID => $max,
            self::COLUMN_USER => $properties[self::COLUMN_USER],
            self::COLUMN_TITLE => $properties[self::COLUMN_TITLE],
            self::COLUMN_PRICE => $properties[self::COLUMN_PRICE],
            self::COLUMN_DESCRIPTION => $properties[self::COLUMN_DESCRIPTION],
            self::COLUMN_CATEGORY => $properties[self::COLUMN_CATEGORY],
            self::COLUMN_MAIN_PHOTO => $properties[self::COLUMN_MAIN_PHOTO]];
        $this->addOrEditOfferSendMail($properties[self::COLUMN_USER],
            $properties[self::COLUMN_TITLE],
            $properties[self::COLUMN_PRICE],
            $properties[self::COLUMN_DESCRIPTION], $max, false);
        return $this->database->table(self::TABLE_NAME)->insert($array);
    }


    public function addOrEditOfferSendMail($userID, $title, $price, $description,
                                           $offerID, $edit = false){
        $user = $this->userManager->get($userID);
        $canSend = $user[UserManager::COLUMN_EMAIL_SUBSCRIPTION];
        if($canSend){
            $template = $this->sender->createTemplate();
            $file = $edit === true ? __DIR__ . '/emailTemplates/editOffer.latte' :
                __DIR__ . '/emailTemplates/addOffer.latte';
            $template->setFile($file);
            $template->offerId = $offerID;
            $template->userId = $userID;
            $template->title = $title;
            $template->price = $price;
            $template->description = mb_strlen($description) > 50 ?
                mb_substr($description, 0, 50)."..." : $description;
            $message = new Message;
            $subject = $edit === true ? "Editace Vaší položky ve vašem účtu Bubovický bazar" :
                "Přidání nové položky ve vašem účtu Bubovický bazar";
            $message->setFrom("supdaniel@seznam.cz")
                ->setSubject($subject)
                ->addTo($user[UserManager::COLUMN_EMAIL])
                ->setHtmlBody($template);
            $mailer = new SendmailMailer;
            $mailer->send($message);
        }
    }

    public function editOffer($id, $properties){
        $array = [self::COLUMN_ID => $id,
            self::COLUMN_USER => $properties[self::COLUMN_USER],
            self::COLUMN_TITLE => $properties[self::COLUMN_TITLE],
            self::COLUMN_PRICE => $properties[self::COLUMN_PRICE],
            self::COLUMN_DESCRIPTION => $properties[self::COLUMN_DESCRIPTION],
            self::COLUMN_CATEGORY => $properties[self::COLUMN_CATEGORY],
            self::COLUMN_MAIN_PHOTO => $properties[self::COLUMN_MAIN_PHOTO]];
        $offer = $this->database->table(self::TABLE_NAME)->get($id);
        if(empty($offer)){
            throw new Nette\Neon\Exception("Nabídka s tímto ID nebyla nalezena.");
        }
        $offer->update($array);
        $this->addOrEditOfferSendMail($properties[self::COLUMN_USER],
            $properties[self::COLUMN_TITLE],
            $properties[self::COLUMN_PRICE],
            $properties[self::COLUMN_DESCRIPTION], $id, true);
    }

    public function getOffersByUser($user){
        return $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_USER, $user);
    }


    public function getOffersByCity($city){
        $allOffers = $this->database->table(self::TABLE_NAME);
        $offers = array();
        foreach($allOffers as $offer){
            $userID = $offer[self::COLUMN_USER];
            $user = $this->userManager->get($userID);
            if($user[UserManager::COLUMN_CITY] == $city){
                array_push($offers, $offer);
            }
        }
        return $offers;
    }

    public function removePhotos($id){
        $photos = $this->photoManager->getPhotosByOffer($id);
        foreach($photos as $photo){
            $filename = $photo[PhotoManager::COLUMN_PATH];
            $id = $photo[PhotoManager::COLUMN_ID];
            unlink(__DIR__ . "/../../www/images/offers/".$filename);
            $this->photoManager->remove($id);
        }
    }

    public function filterOffer($offer, $category,
                                $title = null, $priceFrom = null, $priceTo = null){
        if($offer{self::COLUMN_CATEGORY} != $category && $category !== null){
            return false;
        }
        $isHigherThanLowest = $priceFrom === null || $offer[self::COLUMN_PRICE] >= $priceFrom;
        $isLowerThanHighest = $priceTo === null || $offer[self::COLUMN_PRICE] <= $priceTo;
        if($title === null){
            return $isHigherThanLowest && $isLowerThanHighest;
        }
        $textInTitle = mb_substr_count($offer[self::COLUMN_TITLE], $title) >= 1;
        $textInDescription = mb_substr_count($offer[self::COLUMN_DESCRIPTION], $title) >= 1;
        return $isHigherThanLowest && $isLowerThanHighest && $textInTitle
            && $textInDescription;
    }

    public function getOffersByCriterion($category, $city,
       $title = null, $priceFrom = null, $priceTo = null, $page = 1){
        $allOffers = $this->getOffersByCity($city);
        $offers = array();
        foreach($allOffers as $offer){
            if($this->filterOffer($offer, $category, $title, $priceFrom, $priceTo)){
                array_push($offers, $offer);
            }
        }
        return $offers;
    }

    public function getOffersByCitiesAndCriterion($category, $cities, $title = null, $priceFrom = null, $priceTo = null, $page = 1){
        $offers = array();
        foreach($cities as $city){
            $offersInCity = $this->getOffersByCriterion($category, $city, $title, $priceFrom, $priceTo);
            foreach($offersInCity as $offer){
                array_push($offers, $offer);
            }
        }
        return $offers;
    }

    public function getCountOffersByCitiesAndCriterionWithSubcategories($category, $cities, $title = null, $priceFrom = null, $priceTo = null){
        $count = 0;
        $subcategories = $this->categoryManager->getSubcategories($category);
        foreach($cities as $city){
            $offersInCity = $this->getOffersByCriterion($category, $city, $title, $priceFrom, $priceTo);
            foreach($offersInCity as $offer){
                $count++;
            }
            foreach($subcategories as $subcategory){
                $subcategoryID = $subcategory[CategoryManager::COLUMN_ID];
                $offers = $this->getOffersByCriterion($subcategoryID, $city, $title, $priceFrom, $priceTo);
                foreach($offers as $offer){
                    $count++;
                }
            }
        }
        return $count;
    }

	public function removeOffersByUser($user){
		$offers = $this->getOffersByUser($user);
		foreach($offers as $offer){
			$this->removeOffer($offer[self::COLUMN_ID]);
		}
	}
	
    public function removeOffer($id){
        $this->commentManager->removeCommentsByOffer($id);
        $this->removePhotos($id);
        $this->remove($id);
    }

    public function removePhotoAndChangeMain($id){
        $photo = $this->photoManager->get($id);
        $filename = $photo[PhotoManager::COLUMN_PATH];
        $offerID = $photo[PhotoManager::COLUMN_OFFER];
        $countPhotos = $this->photoManager->getCountPhotosByOffer(intval($offerID));
        if($countPhotos > 1){
            $offer = $this->database->table(self::TABLE_NAME)->get($offerID);
            $mainPhoto = $offer[self::COLUMN_MAIN_PHOTO];
            $this->photoManager->removePhoto($id);
            if($filename === $mainPhoto){
                $photos = $this->photoManager->getPhotosByOffer(intval($offerID));
                $mainPhoto = null;
                foreach($photos as $photo){
                    $mainPhoto = $photo[PhotoManager::COLUMN_PATH];
                    break;
                }
                $offer->update([
                    self::COLUMN_MAIN_PHOTO => $mainPhoto
                ]);
            }
        }
    }

    public function setMainPhoto($photoID){
        $photo = $this->photoManager->get($photoID);
        $photoPath = $photo[PhotoManager::COLUMN_PATH];
        $offerID = $photo[PhotoManager::COLUMN_OFFER];
        $offer = $this->database->table(self::TABLE_NAME)->get($offerID);
        $mainPhotoID = $this->photoManager->getIDOfMainPhoto($offerID);
        $mainPhoto = $this->photoManager->get($mainPhotoID);
        $newPath = $mainPhoto[PhotoManager::COLUMN_PATH];
        $mainPhoto->update([
            PhotoManager::COLUMN_PATH => $photoPath
        ]);
        $photo->update([
           PhotoManager::COLUMN_PATH => $newPath
        ]);
        $offer->update([
                self::COLUMN_MAIN_PHOTO => $photoPath
            ]
        );
    }

    public function getOffersForSearching($text, $cities = [BaseManager::CITY]){
        $allOffers = $this->database->table(self::TABLE_NAME);
        $offers = array();
        foreach($allOffers as $offer){
            $title = $offer[self::COLUMN_TITLE];
            $description = $offer[self::COLUMN_DESCRIPTION];
            $userId = $offer[self::COLUMN_USER];
            $user = $this->userManager->get($userId);
            $cityOfUser = $user[UserManager::COLUMN_CITY];
            $exists = false;
            foreach($cities as $city){
                if($city === $cityOfUser){
                    $exists = true;
                    break;
                }
            }
            if($exists === false){
                continue;
            }
            if(mb_substr_count($title, $text) > 0 || mb_substr_count($description, $text) > 0){
                array_push($offers, $offer);
            }
        }
        return $offers;
    }
	
	public function getOffersOnPage($offers, $page){
		$offersOnThePage = array();
		$index = 0;
		foreach($offers as $offer){
				if($index >= ($page - 1)*BaseManager::PAGE_SIZE
				&& $index < $page*BaseManager::PAGE_SIZE){
					array_push($offersOnThePage, $offer);
				} else if($index == $page*BaseManager::PAGE_SIZE){
					break;
				}
				$index++;
		}
        return $offersOnThePage;
	}
}