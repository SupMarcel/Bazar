<?php

namespace App\Presenters;
use App\Control\CommentControl;
use App\Forms\CommentFormFactory;
use App\Forms\OfferFormFactory;
use App\Model\BaseManager;
use App\Model\CategoryManager;
use App\Model\CityManager;
use App\Model\CommentManager;
use App\Model\OfferManager;
use App\Model\PhotoManager;
use App\Model\UserManager;
use Nette\Application\UI\Multiplier;
use Nette\Forms\Form;
use Nette\Neon\Exception;
use Nette\Security\User;
use Nette\Utils\Json;

/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 22:17
 */
class OfferPresenter extends BasePresenter
{
    /** @var  OfferManager */
    private $offerManager;
    /** @var  CategoryManager */
    private $categoryManager;
    /** @var  UserManager */
    private $userManager;
    /** @var  CityManager */
    private $cityManager;
    /** @var  OfferFormFactory */
    private $offerFormFactory;
    /** @var  CommentManager */
    private $commentManager;
    /** @var  CommentFormFactory */
    private $commentFormFactory;
    /** @var  PhotoManager */
    private $photoManager;
    /** @persistent */
    public $cityArray;
    /** @persistent */
    public $priceFrom;
    /** @persistent */
    public $priceTo;
    private $offer;
    private $filesForUpload;

    /**
     * OfferPresenter constructor.
     * @param OfferManager $offerManager
     * @param CategoryManager $categoryManager
     * @param UserManager $userManager
     * @param CityManager $cityManager
     * @param OfferFormFactory $offerFormFactory
     * @param CommentManager $commentManager
     * @param CommentFormFactory $commentFormFactory
     */
    public function __construct(OfferManager $offerManager,
                                CategoryManager $categoryManager,
                                UserManager $userManager,
                                CityManager $cityManager,
                                OfferFormFactory $offerFormFactory,
                                CommentManager $commentManager,
                                CommentFormFactory $commentFormFactory,
                                PhotoManager $photoManager){
        $this->offerManager = $offerManager;
        $this->categoryManager = $categoryManager;
        $this->userManager = $userManager;
        $this->cityManager = $cityManager;
        $this->offerFormFactory = $offerFormFactory;
        $this->commentManager = $commentManager;
        $this->commentFormFactory = $commentFormFactory;
        $this->photoManager =$photoManager;
        $this->offer = null;
        if(!isset($this->cityArray)){
            $this->cityArray = "".BaseManager::CITY;
        }
        if(!isset($this->priceFrom)){
            $this->priceFrom = null;
        }
        if(!isset($this->priceTo)){
            $this->priceTo = null;
        }
        if(!isset($this->filesForUpload)){
            $this->filesForUpload = [];
        }
    }


    public function actionRemove($id){
        $_SESSION["fileArray"] = [];
        $this->offerManager->removeOffer($id);
        return $this->redirect(303, "Offer:manage");
    }

    public function actionremoveall(){
        $_SESSION["fileArray"] = [];
        $userID = $this->getUser()->id;
        $offers = $this->offerManager->getOffersByUser($userID);
        foreach($offers as $offer){
            $id = $offer[OfferManager::COLUMN_ID];
            $this->offerManager->removeOffer($id);
        }
        return $this->redirect(303, "Offer:manage");
    }

    public function actionDetail($id){
        $this->offer = intval($id);
        $_SESSION["fileArray"] = [];
    }

    public function createComponentAddForm(){
        $form = $this->offerFormFactory->createForm($this->getUser()->id);
        $form->onSuccess[] = function(Form $form, $values){
            $photos = $_SESSION["fileArray"];
            if(count($photos) == 0){
                $form->addError("Pros??m nahrajte alespo?? jednu fotografii.");
            } else {
				$mainPhotoTitle = null;
				foreach($photos as $photoFilename){
                    if(empty($mainPhotoTitle)){
						$mainPhotoTitle = $photoFilename;
						break;
					}
                }
                $allValues = [
                    OfferManager::COLUMN_TITLE => $values["title"],
                    OfferManager::COLUMN_PRICE => $values["price"],
                    OfferManager::COLUMN_DESCRIPTION => $values["description"],
                    OfferManager::COLUMN_CATEGORY => $values["category"],
                    OfferManager::COLUMN_MAIN_PHOTO => $mainPhotoTitle,
                    OfferManager::COLUMN_USER => $this->getUser()->id
                ];
                $row = $this->offerManager->addOffer($allValues);
                $offerID = $row[OfferManager::COLUMN_ID];
                foreach($photos as $photoFilename){
                    $photoValues = [PhotoManager::COLUMN_PATH => $photoFilename,
                        PhotoManager::COLUMN_OFFER => $offerID];
                    $this->photoManager->addPhoto($photoValues);
                }
                $_SESSION["fileArray"] = [];
                $this->redirect(303, "Offer:manage");
            }
        };
        return $form;
    }


    public function createComponentEditForm(){
        return new Multiplier(function($offerId){
            $id = intval($offerId);
			$_SESSION["offerID"] = $id;
            $form = $this->offerFormFactory->createEditForm($id, $this->getUser()->id);
            $form->onSuccess[] = function(Form $form, $values){
				$photos = $_SESSION["fileArray"];
				if(count($photos) == 0){
					$form->addError("Pros??m nahrajte alespo?? jednu fotografii.");
				} else {
					$mainPhotoTitle = null;
					foreach($photos as $photoFilename){
						if(empty($mainPhotoTitle)){
							$mainPhotoTitle = $photoFilename;
							break;
						}
					}
					$allValues = [
						OfferManager::COLUMN_TITLE => $values["title"],
						OfferManager::COLUMN_PRICE => $values["price"],
						OfferManager::COLUMN_DESCRIPTION => $values["description"],
						OfferManager::COLUMN_CATEGORY => $values["category"],
						OfferManager::COLUMN_MAIN_PHOTO => $mainPhotoTitle,
						OfferManager::COLUMN_USER => $this->getUser()->id
					];
					$row = $this->offerManager->editOffer($_SESSION["offerID"], $allValues);
					$_SESSION["fileArray"] = [];
					$this->flashMessage("??sp????n?? upraveno.");
					$this->redirect(303, "Offer:manage");
				}
            };
            return $form;
        });
    }

    public function createComponentAddCommentForm(){
        $form = $this->commentFormFactory->create($this->getUser()->id, intval($this->offer));
        return $form;
    }

    public function createComponentComment(){
        return new Multiplier(function($id){
           return new CommentControl($this->commentManager, $this->userManager, $this->commentFormFactory,
               $this->getUser()->id, $this->offer, $id);
        });
    }

    public function createComponentFilterForm(){
        $form = $this->offerFormFactory->createFilterForm();
        $cityArray = $this->stringToCityArray($this->cityArray);
        $form->setDefaults(["cities" => $cityArray]);
        $form->onSuccess[] = function(Form $form, $values){
            $cities = $form->getValues("cities");
            $noError = true;
            if(empty($cities["cities"]) || count($cities["cities"]) == 0){
                $form->addError("Pros??m zvolte alespo?? jednu obec.");
                $noError = false;
            }
            if($form->values["priceTo"] < $form->values["priceFrom"] && !empty($form->values["priceTo"])){
                $form->addError("Horn?? hranice ceny nem????e b??t ni?????? ne?? spodn?? hranice.");
                $noError = false;
            }
            if($noError === true){
                $cityArray = $values["cities"];
                $this->priceFrom = $values["priceFrom"];
                $this->priceTo = $values["priceTo"];
                $this->cityArray = "";
                $first = true;
                foreach($cityArray as $city){
                    $character = $first === true ? "" : ",";
                    $this->cityArray.=$character.$city;
                    $first = false;
                }
				$this->redirect("Offer:list");
            }
        };
        return $form;
    }



    function stringToCityArray($string){
        $array = explode(",",$string);
        $cityArray = array();
        foreach($array as $IDString){
            array_push($cityArray, intval($IDString));
        }
        return $cityArray;
    }

    public function handleRemovephoto(){
        if($this->isAjax()){
            $photoID = $this->getParameter("photoID");
            $this->offerManager->removePhotoAndChangeMain($photoID);
            $this->redrawControl("allPhotos");
        }
    }

    public function handleChangemainphoto(){
        if($this->isAjax()){
            $photoID = $this->getParameter("photoID");
            $this->offerManager->setMainPhoto($photoID);
            $this->redrawControl("allPhotos");
        }
    }

    public function handleUploadPhotosInsertForm(){
        if($this->isAjax()){
            $countFiles = intval($_POST["countFiles"]);
            for($i = 0; $i < $countFiles; $i++){
                $tmpName = $_FILES["image".$i]["tmp_name"];
                $fileName = $_FILES["image".$i]["name"];
                $path = __DIR__."/../../www/images/offers/".$fileName;
                while(file_exists($path)){
                    $fileName = "0".$fileName;
                    $path = __DIR__."/../../www/images/offers/".$fileName;
                }
                move_uploaded_file($tmpName, $path);
                array_push($_SESSION["fileArray"], $fileName);
            }
            $this->redrawControl("photos");
        }
    }

    public function handleRemovePhotoInsertForm(){
        if($this->isAjax()){
            $fileName = $this->getParameter("filename");
            if(file_exists(__DIR__."/../../www/images/offers/".$fileName)){
                unlink(__DIR__."/../../www/images/offers/".$fileName);
            }
            $key = array_search($fileName, $_SESSION["fileArray"]);
            if( $key !== false ) {
                unset( $_SESSION["fileArray"][ $key ] );
            }
            $this->redrawControl("photos");
        }
    }

    public function handleUploadfiles(){
        if($this->isAjax()){
            $offerID = intval($_POST["offerID"]);
            $countFiles = intval($_POST["countFiles"]);
            for($i = 0; $i < $countFiles; $i++){
                $fileName = $_FILES["image".$i]["name"];
                $tmpName = $_FILES["image".$i]["tmp_name"];
                $path = __DIR__."/../../www/images/offers/".$fileName;
                while(file_exists($path)){
                    $fileName = "0".$fileName;
                    $path = __DIR__."/../../www/images/offers/".$fileName;
                }
                $photoArray = [PhotoManager::COLUMN_OFFER => $offerID,
                        PhotoManager::COLUMN_PATH => $fileName];
                $this->photoManager->addPhoto($photoArray);
                move_uploaded_file($tmpName, $path);
            }
            $this->redrawControl("allPhotos");
        }
    }

    public function renderAdd(){
        if(!isset($_SESSION["fileArray"])){
            $_SESSION["fileArray"] = [];
        }
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
            $username = $user[UserManager::COLUMN_NAME];
            $this->template->username = $username;
            $icon = $user[UserManager::COLUMN_ICON];
            $this->template->icon = $icon;
            $sex = $user[UserManager::COLUMN_SEX];
            $this->template->sex = $sex;
        }
    }

    public function renderDetail($id){
        $_SESSION["fileArray"] = [];
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
            $username = $user[UserManager::COLUMN_NAME];
            $this->template->usernameLogged = $username;
            $icon = $user[UserManager::COLUMN_ICON];
            $this->template->icon = $icon;
            $sex = $user[UserManager::COLUMN_SEX];
            $this->template->sex = $sex;
        }

        $offer = $this->offerManager->get(intval($id));
        $categories = array();
        $category  = $offer[OfferManager::COLUMN_CATEGORY];
        $categoryEntity = $this->categoryManager->get($category);
        while($categoryEntity != null){
            $categoryID = $categoryEntity[CategoryManager::COLUMN_ID];
            $categoryTitle = $categoryEntity[CategoryManager::COLUMN_TITLE];
            $categoryArray = ["id" => $categoryID, "title" => $categoryTitle];
            array_push($categories, $categoryArray);
            $parentCategory = $categoryEntity[CategoryManager::COLUMN_PARENT_CATEGORY];
            $categoryEntity = $this->categoryManager->get($parentCategory);
        }
        $this->template->categoryHierarchy = array_reverse($categories);
        $this->template->offer = $offer;
        $this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->description = OfferManager::COLUMN_DESCRIPTION;
        $this->template->mainPhoto = OfferManager::COLUMN_MAIN_PHOTO;
        $userID = $offer[OfferManager::COLUMN_USER];
        $user = $this->userManager->get($userID);
        $this->template->author = $user;
        $this->template->username = UserManager::COLUMN_NAME;
        $this->template->firstname = UserManager::COLUMN_FIRSTNAME;
        $this->template->lastname = UserManager::COLUMN_LASTNAME;
        $this->template->time = UserManager::COLUMN_TIME;
        $this->template->email = UserManager::COLUMN_EMAIL;
        $this->template->phone = UserManager::COLUMN_PHONE;
        $cityID = $user[UserManager::COLUMN_CITY];
        $city = $this->cityManager->get($cityID);
        $this->template->city = $city[CityManager::COLUMN_TITLE];
        $this->template->comments = $this->commentManager->getCommentsByOffer(intval($id));
        $this->template->commentID = CommentManager::COLUMN_ID;
        $this->template->images = $this->photoManager->getPhotosByOffer(intval($id));
        $this->template->imagePath = PhotoManager::COLUMN_PATH;
    }

    public function renderEdit($id){
        $allPhotos = $this->photoManager->getPhotosByOffer(intval($id));
		$_SESSION["fileArray"] = [];
		foreach($allPhotos as $photo){
			array_push($_SESSION["fileArray"], $photo[PhotoManager::COLUMN_PATH]);
		}
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
            $username = $user[UserManager::COLUMN_NAME];
            $this->template->username = $username;
            $icon = $user[UserManager::COLUMN_ICON];
            $this->template->icon = $icon;
            $sex = $user[UserManager::COLUMN_SEX];
            $this->template->sex = $sex;
        }
        $this->template->offerID = $id;
        $this->template->photos = $this->photoManager->getPhotosByOffer(intval($id));
        $this->template->photoID = PhotoManager::COLUMN_ID;
        $this->template->photoPath = PhotoManager::COLUMN_PATH;
        $this->template->moreThanOnePhoto = $this->photoManager->getCountPhotosByOffer(intval($id)) > 1;
        $this->template->mainPhoto = $this->offerManager->get(intval($id))[OfferManager::COLUMN_MAIN_PHOTO];
    }

    public function renderManage(){
        if(!isset($_SESSION["fileArray"])){
            $_SESSION["fileArray"] = [];
        }
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
            $username = $user[UserManager::COLUMN_NAME];
            $this->template->username = $username;
            $icon = $user[UserManager::COLUMN_ICON];
            $this->template->icon = $icon;
            $sex = $user[UserManager::COLUMN_SEX];
            $this->template->sex = $sex;
        }
        $user = $this->getUser()->id;
        $this->template->offers = $this->offerManager->getOffersByUser($user);
        $this->template->offerID = OfferManager::COLUMN_ID;
        $this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->description = OfferManager::COLUMN_DESCRIPTION;
        $this->template->image = OfferManager::COLUMN_MAIN_PHOTO;
        $this->template->photoID = PhotoManager::COLUMN_ID;
        $this->template->photoPath = PhotoManager::COLUMN_PATH;
        $this->template->imagesForUpload = $_SESSION["fileArray"];
        $this->template->addFilter('first50', function($description){
            if(empty($description)){
                return "";
            }
            if(mb_strlen($description) <= 50){
                return $description;
            }
            return mb_substr($description, 0, 50)."...";
        });
    }

    public function renderSearch($text = "", $page = 1){
        if(!isset($_SESSION["fileArray"])){
            $_SESSION["fileArray"] = [];
        }
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
            $username = $user[UserManager::COLUMN_NAME];
            $this->template->username = $username;
            $icon = $user[UserManager::COLUMN_ICON];
            $this->template->icon = $icon;
            $sex = $user[UserManager::COLUMN_SEX];
            $this->template->sex = $sex;
        }
		
        $cityArray = $this->stringToCityArray($this->cityArray);
		$offerArray = $this->offerManager->getOffersForSearching($text, $cityArray);
		$offerCount = count($offerArray);
			$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$lastPage = ($offerCount % BaseManager::PAGE_SIZE == 0) ? intval($offerCount/BaseManager::PAGE_SIZE) : intval($offerCount/BaseManager::PAGE_SIZE) + 1;
			$goodsOnThePage = $this->offerManager->getOffersOnPage($offerArray, $page);
			
			shuffle($goodsOnThePage);
			$this->template->page = $page;
        $this->template->lastPage = $lastPage;
		$this->template->noItems = $offerCount == 0;
		if(strpos($actual_link,"&page=") === FALSE){
			$this->template->linkToFirst = $actual_link."&page=1";
			$this->template->linkToPrevious = $actual_link."&page=".($page-1);
			$this->template->linkToNext = $actual_link."&page=".($page+1);
			$this->template->linkToLast = $actual_link."&page=".$lastPage;
		} else {
			$this->template->linkToFirst = str_replace("page=".$page, "page=1", $actual_link);
			$this->template->linkToPrevious = str_replace("page=".$page, "page=".($page-1), $actual_link);
			$this->template->linkToNext = str_replace("page=".$page, "page=".($page+1), $actual_link);
			$this->template->linkToLast = str_replace("page=".$page, "page=".$lastPage, $actual_link);
		}
		
        $this->template->offers = $goodsOnThePage;
        $this->template->noCategory = true;
        $mainCategories = $this->categoryManager->getSubcategories(null);
        $categories = array();
        foreach($mainCategories as $mainCategory){
            $id = $mainCategory[CategoryManager::COLUMN_ID];
            $title = $mainCategory[CategoryManager::COLUMN_TITLE];
            $image = $mainCategory[CategoryManager::COLUMN_IMAGE];
            $countOffers = $this->offerManager->getCountOffersByCitiesAndCriterionWithSubcategories($id, $cityArray,
                null, $this->priceFrom, $this->priceTo);
            $categories[$id] = ["title" => $title, "countOffers" => $countOffers, "image" => $image];
        }
        $this->template->categories = $categories;
        $this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->image = OfferManager::COLUMN_MAIN_PHOTO;
        $this->template->offerOwner = OfferManager::COLUMN_USER;
        $this->template->addFilter("cityName", function($id){
            $user = $this->userManager->get($id);
            $cityID = $user[UserManager::COLUMN_CITY];
            $city = $this->cityManager->get($cityID);
            return $city[CityManager::COLUMN_TITLE];
        });
    }

    public function renderList($categoryID = null, $page = 1){
        if(!isset($_SESSION["fileArray"])){
            $_SESSION["fileArray"] = [];
        }
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
            $username = $user[UserManager::COLUMN_NAME];
            $this->template->username = $username;
            $icon = $user[UserManager::COLUMN_ICON];
            $this->template->icon = $icon;
            $sex = $user[UserManager::COLUMN_SEX];
            $this->template->sex = $sex;
        }
        $cityArray = $this->stringToCityArray($this->cityArray);
		$goodsInTheCategory = $this->offerManager->getOffersByCitiesAndCriterion($categoryID,$cityArray,
            null, $this->priceFrom, $this->priceTo);
			$goodsOnThePage = array();
			foreach($goodsInTheCategory as $goodInTheCategory){
				array_push($goodsOnThePage, $goodInTheCategory);
			}
			$mainCategories = $this->categoryManager->getSubcategories($categoryID);
			foreach($mainCategories as $mainCategory){
				$id = $mainCategory[CategoryManager::COLUMN_ID];
				$goodsInTheSubcategory = $this->offerManager->getOffersByCitiesAndCriterion($id,$cityArray,
            null, $this->priceFrom, $this->priceTo);
				foreach($goodsInTheSubcategory as $goodInTheSubcategory){
					array_push($goodsOnThePage, $goodInTheSubcategory);
				}
			}
			$offerCount = count($goodsOnThePage);
			$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$lastPage = ($offerCount % BaseManager::PAGE_SIZE == 0) ? intval($offerCount/BaseManager::PAGE_SIZE) : intval($offerCount/BaseManager::PAGE_SIZE) + 1;
			$goodsOnThePage = $this->offerManager->getOffersOnPage($goodsOnThePage, $page);
			
			shuffle($goodsOnThePage);
			$this->template->noItems = $offerCount == 0;
			$this->template->page = $page;
        $this->template->lastPage = $lastPage;
		if(strpos($actual_link,"&page=") === FALSE){
			$this->template->linkToFirst = $actual_link."&page=1";
			$this->template->linkToPrevious = $actual_link."&page=".($page-1);
			$this->template->linkToNext = $actual_link."&page=".($page+1);
			$this->template->linkToLast = $actual_link."&page=".$lastPage;
		} else {
			$this->template->linkToFirst = str_replace("page=".$page, "page=1", $actual_link);
			$this->template->linkToPrevious = str_replace("page=".$page, "page=".($page-1), $actual_link);
			$this->template->linkToNext = str_replace("page=".$page, "page=".($page+1), $actual_link);
			$this->template->linkToLast = str_replace("page=".$page, "page=".$lastPage, $actual_link);
		}
			
        $this->template->offers = $goodsOnThePage;
        $this->template->noCategory = ($categoryID === null);
        $parentCategories = array();
        if($categoryID !== null){
            $category = $this->categoryManager->get($categoryID);
            while(true){
                $title = $category[CategoryManager::COLUMN_TITLE];
                $id = $category[CategoryManager::COLUMN_ID];
                $parentCategory = $category[CategoryManager::COLUMN_PARENT_CATEGORY];
                $last = ($id === intval($categoryID));
                array_push($parentCategories, ["id" => $id, "title" => $title, "last" => $last]);
                if($parentCategory === null){
                    break;
                } else {
                    $category = $this->categoryManager->get($parentCategory);
                }
            }
        }
        $this->template->parentCategories = array_reverse($parentCategories);
        $cityArray = $this->stringToCityArray($this->cityArray);
        $categories = array();
        foreach($mainCategories as $mainCategory){
            $id = $mainCategory[CategoryManager::COLUMN_ID];
            $title = $mainCategory[CategoryManager::COLUMN_TITLE];
            $image = $mainCategory[CategoryManager::COLUMN_IMAGE];
            $countOffers = $this->offerManager->getCountOffersByCitiesAndCriterionWithSubcategories($id,$cityArray,
                null, $this->priceFrom, $this->priceTo);
            $categories[$id] = ["title" => $title, "countOffers" => $countOffers, "image" => $image];
        }
        $this->template->categories = $categories;
        $this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->image = OfferManager::COLUMN_MAIN_PHOTO;
        $this->template->offerOwner = OfferManager::COLUMN_USER;
        $this->template->addFilter("cityName", function($id){
            $user = $this->userManager->get($id);
            $cityID = $user[UserManager::COLUMN_CITY];
            $city = $this->cityManager->get($cityID);
            return $city[CityManager::COLUMN_TITLE];
        });$this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->image = OfferManager::COLUMN_MAIN_PHOTO;
        $this->template->offerOwner = OfferManager::COLUMN_USER;
        $this->template->addFilter("cityName", function($id){
            $user = $this->userManager->get($id);
            $cityID = $user[UserManager::COLUMN_CITY];
            $city = $this->cityManager->get($cityID);
            return $city[CityManager::COLUMN_TITLE];
        });
    }
}