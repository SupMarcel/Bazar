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
use App\Model\AddressManager;
use  Nette\Utils\ArrayHash;
use Tracy\ILogger;
use Nette\Database\Explorer;

class OfferManager extends BaseManager
{
    const
        TABLE_NAME = 'offer',
        COLUMN_ID = 'id',
        COLUMN_USER = 'user_id',
        COLUMN_TITLE = 'product_title',
        COLUMN_PRICE = 'price',
        COLUMN_DESCRIPTION = 'product_description',
        COLUMN_CATEGORY='category',
        COLUMN_MAIN_PHOTO='main_photo';

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
    
    private $addressManager;

    public function __construct(Nette\Database\Explorer $database, CategoryManager $categoryManager,
                                UserManager $userManager, ILogger $logger,
        CommentManager $commentManager, PhotoManager $photoManager, Sender $sender, AddressManager $addressManager)
    {
        parent::__construct($database,$logger);
        $this->categoryManager = $categoryManager;
        $this->userManager = $userManager;
        $this->commentManager = $commentManager;
        $this->photoManager = $photoManager;
        $this->sender = $sender;
        $this->addressManager = $addressManager;
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
            $file = $edit === true ? __DIR__ . '/emailTemplates/editOffer.latte' : __DIR__ . '/emailTemplates/addOffer.latte';
            $template->setFile($file);
            $template->offerId = $offerID;
            $template->userId = $userID;
            $template->title = $title;
            $template->price = $price;
            $template->description = mb_strlen($description) > 50 ? mb_substr($description, 0, 50)."..." : $description;
            $message = new Message;
            $subject = $edit === true ? "Editace Vaší položky ve vašem účtu Bubovický bazar" :
                "Přidání nové položky ve vašem účtu Bubovický bazar";
            $message->setFrom("order@localhost.cz")
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
    
    public function countOrders() {
        return $this->database->table(self::TABLE_NAME)->count('*');
    }

    public function getOffersTable($params, $categoryId){
        $table = $this->database->table(self::TABLE_NAME);
        if(!empty($categoryId)) {
            $table->where(self::COLUMN_CATEGORY, $categoryId); 
            }     
        if(!empty($params[self::COLUMN_TITLE])) {
           $table->where('MATCH(' . self::COLUMN_TITLE . ', ' . self::COLUMN_DESCRIPTION . ') AGAINST (?)', $params[self::COLUMN_TITLE]);
        }
        if(!empty($params["max".self::COLUMN_PRICE])) {
            $table->where( self::COLUMN_PRICE.'<= ? ',  $params["max".self::COLUMN_PRICE]);
        }
        if(!empty($params["min".self::COLUMN_PRICE])) {
            $table->where(self::COLUMN_PRICE.'>= ?', ($params["min".self::COLUMN_PRICE]));
        }
        if(!empty($params["seller_name"])) {
            bdump($params["seller_name"]);
            $table->where(self::COLUMN_USER, $params["seller_name"]);
        }
        if(!empty($params["min".AddressManager::COLUMN_LATITUDE])&&!empty($params["max".AddressManager::COLUMN_LATITUDE])&&!empty($params["min".AddressManager::COLUMN_LONGITUDE])&&!empty($params["max".AddressManager::COLUMN_LONGITUDE])) {
           $filterByLatitude = UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LATITUDE. "> ? AND ".UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LATITUDE." < ?";
            $table->where($filterByLatitude,
                          ($params["min".AddressManager::COLUMN_LATITUDE]),
                          ($params["max".AddressManager::COLUMN_LATITUDE]));
            $filterByLongitude = UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LONGITUDE. "> ? AND ".UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LONGITUDE." < ?";
            $table->where($filterByLongitude,
                          ($params["min".AddressManager::COLUMN_LONGITUDE]),
                          ($params["max".AddressManager::COLUMN_LONGITUDE]));
        }
        return $table;
        bdump($table);
    }
    
    public function getOfferTableByParams($params = null) {
        $table = $this->database->table(self::TABLE_NAME);
        if(!empty($params[self::COLUMN_CATEGORY])) {
            $categories = $this->categoryManager->getSubcategoryIds($params[self::COLUMN_CATEGORY]);
            if(!empty($categories)){
               $table->where(self::COLUMN_CATEGORY, $categories); 
            }else{
                  $table->where(self::COLUMN_CATEGORY, $params[self::COLUMN_CATEGORY]);
            }     
            
        }
        if(!empty($params[self::COLUMN_TITLE])) {
          $table->where('MATCH(' . self::COLUMN_TITLE . ', ' . self::COLUMN_DESCRIPTION . ') AGAINST (?)', $params[self::COLUMN_TITLE]);
        }
        if(!empty($params["max".self::COLUMN_PRICE])) {
            $table->where( self::COLUMN_PRICE.'<= ? ',  $params["max".self::COLUMN_PRICE]);
        }
        if(!empty($params["min".self::COLUMN_PRICE])) {
            $table->where(self::COLUMN_PRICE.'>= ?', ($params["min".self::COLUMN_PRICE]));
        }
        if(!empty($params["seller_name"])) {
            $table->where(self::COLUMN_USER, $params["seller_name"]);
        }
        if(!empty($params["min".AddressManager::COLUMN_LATITUDE])&&!empty($params["max".AddressManager::COLUMN_LATITUDE])&&!empty($params["min".AddressManager::COLUMN_LONGITUDE])&&!empty($params["max".AddressManager::COLUMN_LONGITUDE])) {
            // $table->joinWhere->()joinWhere(AddressManager::TABLE_NAME, "")
           // $filterByLatitude = "user.active_address.latitude > ? AND user.active_address.latitude < ?";
            $filterByLatitude = UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LATITUDE. "> ? AND ".UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LATITUDE." < ?";
            $table->where($filterByLatitude,
                          ($params["min".AddressManager::COLUMN_LATITUDE]),
                          ($params["max".AddressManager::COLUMN_LATITUDE]));
            $filterByLongitude = UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LONGITUDE. "> ? AND ".UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LONGITUDE." < ?";
            $table->where($filterByLongitude,
                          ($params["min".AddressManager::COLUMN_LONGITUDE]),
                          ($params["max".AddressManager::COLUMN_LONGITUDE]));
        }
        return $table;
        bdump($table);
    }
    
    public function getCountOffers($params = null, $categoryId = null ) {
       $table = $this->database->table(self::TABLE_NAME);
        if(!empty($categoryId)) {
            $table->where(self::COLUMN_CATEGORY, $categoryId); 
            }     
        if(!empty($params[self::COLUMN_TITLE])) {
           $table->where('MATCH(' . self::COLUMN_TITLE . ', ' . self::COLUMN_DESCRIPTION . ') AGAINST (?)', $params[self::COLUMN_TITLE]);
        }
        if(!empty($params["max".self::COLUMN_PRICE])) {
            $table->where( self::COLUMN_PRICE.'<= ? ',  $params["max".self::COLUMN_PRICE]);
        }
        if(!empty($params["min".self::COLUMN_PRICE])) {
            $table->where(self::COLUMN_PRICE.'>= ?', ($params["min".self::COLUMN_PRICE]));
        }
        if(!empty($params["seller_name"])) {
            $table->where(self::COLUMN_USER, $params["seller_name"]);
        }
        if(!empty($params["min".AddressManager::COLUMN_LATITUDE])&&!empty($params["max".AddressManager::COLUMN_LATITUDE])&&!empty($params["min".AddressManager::COLUMN_LONGITUDE])&&!empty($params["max".AddressManager::COLUMN_LONGITUDE])) {
           $filterByLatitude = UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LATITUDE. "> ? AND ".UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LATITUDE." < ?";
            $table->where($filterByLatitude,
                          ($params["min".AddressManager::COLUMN_LATITUDE]),
                          ($params["max".AddressManager::COLUMN_LATITUDE]));
            $filterByLongitude = UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LONGITUDE. "> ? AND ".UserManager::TABLE_NAME.'.'.UserManager::COLUMN_ACTIVE_ADDRESS_ID.'.'.AddressManager::COLUMN_LONGITUDE." < ?";
            $table->where($filterByLongitude,
                          ($params["min".AddressManager::COLUMN_LONGITUDE]),
                          ($params["max".AddressManager::COLUMN_LONGITUDE]));
        }
        return $table->count('*');
    }

    public function removeOffersByUser($user){
	$offers = $this->getOffersByUser($user);
	foreach($offers as $offer){
		$this->removeOffer($offer[self::COLUMN_ID]);
	}
    }
	
    public function removeOffer($id){
        $this->commentManager->removeCommentsByOffer($id);
        $this->photoManager->removePhotos($id);
        $this->remove($id);
    }

    public function setMainPhoto($photoID, $offerID){
        
        $offer = $this->database->table(self::TABLE_NAME)->get($offerID);
        $offer->update([
                self::COLUMN_MAIN_PHOTO => $photoID
            ]
        );
    }

    
    public function getIDOfMainPhoto($offer){
     $row = $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_ID, $offer)->fetch();
     return $row->hlavniFotografie;
    }
    
    

}