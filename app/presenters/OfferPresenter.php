<?php

namespace App\Presenters;
use App\Components\OfferFilterControl;
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
use App\Model\AddressManager;
use Nette\Application\UI\Multiplier;
use Nette\Forms\Form;
use Nette\Neon\Exception;
use Nette\Security\User;
use Nette\Utils\Json;
use Nette\Http\FileUpload;
use App\components\OfferImagesControl;
use Nette\Utils\ArrayHash;
use Nette\Utils\Paginator;
use App\Services\FilterService;


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
    
    private $filterService;

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
                                CityManager $cityManager,
                                OfferFormFactory $offerFormFactory,
                                CommentManager $commentManager,
                                CommentFormFactory $commentFormFactory,
                                FilterService $filterService,
                                PhotoManager $photoManager){
        $this->offerManager = $offerManager;
        $this->categoryManager = $categoryManager;
        $this->cityManager = $cityManager;
        $this->offerFormFactory = $offerFormFactory;
        $this->commentManager = $commentManager;
        $this->commentFormFactory = $commentFormFactory;
        $this->photoManager =$photoManager;
        $this->offer = null;
        $this->filterService = $filterService;
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
        return $this->redirect("Offer:manage");
    }

    public function actionremoveall(){
        $_SESSION["fileArray"] = [];
        $userID = $this->getUser()->id;
        $offers = $this->offerManager->getOffersByUser($userID);
        foreach($offers as $offer){
            $id = $offer[OfferManager::COLUMN_ID];
            $this->offerManager->removeOffer($id);
        }
        return $this->redirect("Offer:manage");
    }

    public function actionDetail($id){
        $this->offer = intval($id);
        $_SESSION["fileArray"] = [];
    }

    public function createComponentAddForm(){
        $form = $this->offerFormFactory->createForm($this->getUser()->id);
        $form->onSuccess[] = function(Form $form, $values){
            $photos = $values["photos"];
            if (empty($_SESSION["arrayPhotoName"])){
                if(!empty($photos)){
                $mainPhotoID = $this->photoManager->insertPhotosfromAjax($photos);
                }else{
                    $mainPhotoID = photoManager::DEFAULT_PHOTO_ID;
                }
            } else {
                if (!empty($photos)){
                $mainPhotoID = $this->photoManager->PhotoID($_SESSION["mainPhotoPath"]);
                }else{
                    $mainPhotoID = photoManager::DEFAULT_PHOTO_ID;
                }
            }

                $allValues = [
                    OfferManager::COLUMN_TITLE => $values["title"],
                    OfferManager::COLUMN_PRICE => $values["price"],
                    OfferManager::COLUMN_DESCRIPTION => $values["description"],
                    OfferManager::COLUMN_CATEGORY => $values["category"],
                    OfferManager::COLUMN_USER => $this->getUser()->id,
                    OfferManager::COLUMN_MAIN_PHOTO => $mainPhotoID
                ];
                $row = $this->offerManager->addOffer($allValues);
                $offerID = $row[OfferManager::COLUMN_ID];
                
                $this->photoManager->editOfferPhotos($offerID);
                
                $_SESSION["arrayPhotoName"] = [];
                $this->redirect("Offer:manage");
            
        };
        return $form;
    }
    
    


    public function createComponentEditForm(){
        return new Multiplier(function($offerId){
            $id = intval($offerId);
	    $_SESSION['offerId'] = $id;
            $form = $this->offerFormFactory->createEditForm($id, $this->getUser()->id);
            $form->onSuccess[] = function(Form $form, $values){
				$photos =  $values["photos"];
                                
                                 if (!empty($_SESSION["mainPhotoPath"])){
                                    $mainPhotoID = $this->photoManager->PhotoID($_SESSION["mainPhotoPath"]);
                                } 
                                 elseif(empty($_SESSION["mainPhotoPath"]) && !empty($photos)){
                                    $mainPhotoID = $this->photoManager->insertPhotosfromAjax($photos, $_SESSION['offerId']);
                                }
                                 else {
                                    $mainPhotoID = PhotoManager::DEFAULT_PHOTO_ID;
                                }
                                
                                $allValues = [
                                    OfferManager::COLUMN_TITLE => $values["title"],
				    OfferManager::COLUMN_PRICE => $values["price"],
				    OfferManager::COLUMN_DESCRIPTION => $values["description"],
                                    OfferManager::COLUMN_CATEGORY => $values["category"],
                                    OfferManager::COLUMN_MAIN_PHOTO => $mainPhotoID,
                                    OfferManager::COLUMN_USER => $this->getUser()->id
				];
				$row = $this->offerManager->editOffer($_SESSION['offerId'], $allValues);
				$_SESSION["arrayPhotoName"] = [];
                                $_SESSION["mainPhotoPath"] = [];
				$this->flashMessage("Úspěšně upraveno.");
				$this->redirect("Offer:manage");
            };
            return $form;
        });
    }
    
    

    public function createComponentAddCommentForm(){
       return $this->commentFormFactory->createFormComment($this->user->id, intval($this->offer), $commentId = null);
    }

    public function createComponentComment(){
        return new Multiplier(function($commentId = null){
        return new CommentControl($this->commentManager, $this->userManager, $this->commentFormFactory,
                $this->offer, $commentId, new Paginator());
        });
    }

    public function createComponentFilterForm($parameters){
        $user = null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
        }    
        $parameters = $this->getRequest()->getParameters();
        $form = $this->offerFormFactory->createFilterForm($parameters, $user);
       
        $form->setMethod("GET");
        
        $form->onSuccess[] = function(Form $form, $values){
            $noError = true;
            if($form->values["priceTo"] < $form->values["priceFrom"] && !empty($form->values["priceTo"])){
                $form->addError("Horní hranice ceny nemůže být nižší než spodní hranice.");
                $noError = false;
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
    
    public function renderRemovePhotoeditOfferPhotosById($photoID){
        
            $this->template->setFile(__DIR__ . '/templates/Offer/edit.latte');
            $offerId = $this->photoManager->get($photoID)[PhotoManager::COLUMN_OFFER];
            $this->offerManager->removePhotoAndChangeMain($photoID);
            $this->renderEdit($offerId);
            $this->redrawControl("allPhotos");
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
        $this->template->comment = null;
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
        
       
        $this->template->comments = $this->commentManager->getCommentsByOffer(intval($id));
        $this->template->commentID = CommentManager::COLUMN_ID;
        $this->template->images = $this->photoManager->getPhotosByOffer(intval($id));
        $this->template->imagePath = PhotoManager::COLUMN_PATH;
        $this->template->tabPhoto = PhotoManager::TABLE_NAME;
    }
    
    public function actionEdit($id) {
        if ($id && ($offer = $this->offerManager->get($id))) {
            $this['offerImages']->setPhotos($offer); // Nastaví již existující produkt do komponenty.
        } else {
            $this['offerImages']->setPhotos();   
        }
    }

    public function renderEdit($id){
        // $this->photoManager->getPhotosByOffer(intval($id));
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
        $this->template->photos = $this->photoManager->getPhotosByOffer(intval($id)); //isset($_SESSION["arrayPhotoName"])?$_SESSION["arrayPhotoName"]:[];
        $this->template->photoID = PhotoManager::COLUMN_ID;
        $this->template->photoPath = PhotoManager::COLUMN_PATH;
        $this->template->moreThanOnePhoto = $this->photoManager->getCountPhotosByOffer(intval($id)) > 1;
        $idPhoto= $this->offerManager->get(intval($id))[OfferManager::COLUMN_MAIN_PHOTO];
        $this->template->mainPhoto = $this->photoManager->get($idPhoto)->cesta;
        
        $this->template->offerImagesWidget = ArrayHash::from(['name' => 'offerImages', 'after' => 'photos']);
    }
    public function actionManage($id=null) {
        if ($id && ($offer = $this->offerManager->get($id))) {
        $this['offerImages']->setPhotos($offer); // Nastaví již existující produkt do komponenty.
       }else{
        $this['offerImages']->setPhotos();   
       }
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
        $this->template->mainPhoto = isset($_SESSION["mainPhoto"]) ? $_SESSION["mainPhoto"]:"";
        $this->template->photoID = PhotoManager::COLUMN_ID;
        $this->template->photoPath = PhotoManager::COLUMN_PATH;
        $this->template->imagesForUpload = isset($_SESSION["arrayPhotoName"])?$_SESSION["arrayPhotoName"]:[];
        $this->template->productImagesWidget = ArrayHash::from(['name' => 'offerImages', 'after' => 'photos']);
        $this->template->offerImagesWidget = ArrayHash::from(['name' => 'offerImages', 'after' => 'photos']);
        
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
    
    protected function createComponentOfferImages()
   {
       return new OfferImagesControl($this->photoManager, $this->offerManager );
   }
   
    protected function createComponentOfferFilter()
   {
       return new OfferFilterControl($this->userManager, $this->offerManager, $this->categoryManager, $this->filterService);
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

    public function renderList($page = 1, $categoryID = null){
        if(!isset($_SESSION["fileArray"])){
            $_SESSION["fileArray"] = [];
        }
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
            $userName = $user[UserManager::COLUMN_NAME];
            $icon = $user[UserManager::COLUMN_ICON];
            $this->template->icon = $icon;
            $sex = $user[UserManager::COLUMN_SEX];
            $this->template->sex = $sex;
            $userLatitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LATITUDE};
            $userLongitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LONGITUDE};
        }else{
            $userName = "";
        }
        $this->template->username = $userName;
        $params = [];
        $mainCategoryIds = $this->categoryManager->getSubcategoryIds($categoryID);
        if (empty($mainCategoryIds)){
            $mainCategoryIds = $categoryID; 
        }
        $params[OfferManager::COLUMN_CATEGORY] = $mainCategoryIds;
        $params[OfferManager::COLUMN_TITLE] = $this->getParameter("titleText");
        $params["max".OfferManager::COLUMN_PRICE] = floatval($this->getParameter("priceTo"));
        $params["min".OfferManager::COLUMN_PRICE] = floatval($this->getParameter("priceFrom"));
        if ($this->getParameter("distance")){
            $params['distance']=floatval($this->getParameter("distance")); 
            $distanceLatitude = 0.009*floatval($this->getParameter("distance"));
            $params["max".AddressManager::COLUMN_LATITUDE]= $userLatitude+$distanceLatitude;
            $params["min".AddressManager::COLUMN_LATITUDE]= $userLatitude-$distanceLatitude;
            $distanceLongitude = 0.00898*floatval($this->getParameter("distance"));
            $params["max".AddressManager::COLUMN_LONGITUDE]= $userLongitude+$distanceLongitude;
            $params["min".AddressManager::COLUMN_LONGITUDE]= $userLongitude-$distanceLongitude;
        }
        
	$paginator = new Paginator;
	$paginator->setItemsPerPage(10); // počet položek na stránce
	$paginator->setPage($page); // číslo aktuální stránky
        $this->template->paginator = $paginator;
        if (!empty($user)){
             $goodsInTheCategory = $this->filterService->getOffersBydistance( $userLatitude, $userLongitude,$params, $paginator->getLength(),$paginator->getOffset());
        }else{
              $goodsInTheCategory = $this->filterService->getOffersByParams($params, $paginator->getLength(),$paginator->getOffset());
         }     
	$offerCount = count($goodsInTheCategory);
        $paginator->setItemCount($offerCount); // celkový počet článků
        //$goodsInTheCategory = $this->offerManager->getOffersByParams($params, $paginator->getLength(), $paginator->getOffset());
	$mainCategories = $this->categoryManager->getSubcategories($categoryID == "all" ? null : $categoryID);
			
        $this->template->offers = $goodsInTheCategory;
        $this->template->offerID = OfferManager::COLUMN_ID;
        $this->template->noCategory = ($categoryID === null || $categoryID === "all");
        $this->template->noItems = empty($goodsInTheCategory);
        
        $urlParameters = $this->getRequest()->getParameters();
        unset($urlParameters["page"]);
        unset($urlParameters["locale"]);
        unset($urlParameters["search"]);
        unset($urlParameters["do"]);
        $this->template->urlParameters = $urlParameters;
        $parentCategories = array();
        if($categoryID !== null && $categoryID != 'all'){
            $category = $this->categoryManager->get($categoryID);
            $parentCategory = $category[CategoryManager::COLUMN_PARENT_CATEGORY];
            while($parentCategory !== null){
                $title = $category[CategoryManager::COLUMN_TITLE];
                $id = $category[CategoryManager::COLUMN_ID];
                $parentCategory = $category[CategoryManager::COLUMN_PARENT_CATEGORY];
                $last = ($id === intval($categoryID));
                array_push($parentCategories, ["id" => $id, "title" => $title, "last" => $last]);
                $category = $this->categoryManager->get($parentCategory);
            }
        }
        $this->template->parentCategories = array_reverse($parentCategories);
        
        $categories = array();
        foreach($mainCategories as $mainCategory){
            $id = $mainCategory[CategoryManager::COLUMN_ID];
            $categoryIds = $id;
            if (empty($categoryID) || $categoryID == "all"){
                $categoryIds = $this->categoryManager->getSubcategoryIds($id);
            }
            $title = $mainCategory[CategoryManager::COLUMN_TITLE];
            $image = $mainCategory[CategoryManager::COLUMN_IMAGE];
            $countOffers = $this->offerManager->getCountOffers($categoryIds);
            $categories[$id] = ["title" => $title, "countOffers" => $countOffers, "image" => $image];
        }
        $this->template->categories = $categories;
        $this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->image = OfferManager::COLUMN_MAIN_PHOTO;
        $this->template->offerOwner = UserManager::TABLE_NAME;
        $this->template->offerOwnerName = UserManager::COLUMN_NAME;
        $this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->image = PhotoManager::TABLE_NAME;
        
        $this->template->photoPath = PhotoManager::COLUMN_PATH;
        
    }
    
     protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->formPathInLine = __DIR__ . '/templates/formInLine.latte'; // Předá cestu ke globální šabloně formulářů do šablony.
        $this->template->formBootstrap = __DIR__ . '/templates/form-bootstrap3.latte'; 
    }
}