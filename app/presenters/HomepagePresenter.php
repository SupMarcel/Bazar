<?php

namespace App\Presenters;


use App\Forms\OfferFormFactory;
use App\Model\BaseManager;
use App\Model\CategoryManager;
use App\Model\OfferManager;
use App\Model\UserManager;
use App\Model\CityManager;
use App\Model\AddressManager;
use App\Model\CommentManager;
use Nette\Forms\Form;
use App\Model\PhotoManager;
use App\Services\FilterService;
use Nette\Utils\Paginator;

class HomepagePresenter extends BasePresenter
{

    /** @var  OfferFormFactory */
    private $offerFormFactory;
    /** @var  CategoryManager */
    private $categoryManager;
    /** @var  OfferManager */
    private $offerManager;
    private $photoManager;
	/** @var CityManager */
    private $cityManager;
	/** @var CommentManager */
    private $commentManager;
    private $filterService;

    public function __construct(OfferFormFactory $offerFormFactory,
    CategoryManager $categoryManager, OfferManager $offerManager, PhotoManager $photoManager, CityManager $cityManager, CommentManager $commentManager, FilterService $filterService )
    {
        $this->offerFormFactory = $offerFormFactory;
        $this->categoryManager = $categoryManager;
        $this->offerManager = $offerManager;
        $this->photoManager = $photoManager;
	$this->cityManager = $cityManager;
	$this->commentManager = $commentManager;
        $this->filterService = $filterService;
    }

    public function actionRemoveuser($id){
	$this->offerManager->removeOffersByUser(intval($id));
	$this->commentManager->removeCommentsByUser(intval($id));
	$this->userManager->remove(intval($id));
	$this->redirect(302, "Homepage:default");
    }

    public function actionCancelEmailSubscription($userId){
        if(!empty($userId)){
            $this->userManager->cancelEmailSubscription($userId);
        }
        $this->redirect(302, "Homepage:default");
    }



    public function renderDefault($page = 1, $kategorie = null)
    {
      $_SESSION["fileArray"] = [];
	
        if($this->getUser()->isLoggedIn()){
            $user = $this->userManager->get($this->getUser()->id);
            $userLatitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LATITUDE};
            $userLongitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LONGITUDE};
        }
        $params = [];
        if (empty($mainCategoryIds)){
            $mainCategoryIds = $kategorie; 
        }
        if(empty(floatval($this->getParameter(OfferManager::COLUMN_CATEGORY)))){
                 $params[OfferManager::COLUMN_CATEGORY] = null;
        }else{
              $params[OfferManager::COLUMN_CATEGORY] = floatval($this->getParameter(OfferManager::COLUMN_CATEGORY));
         }
        $params[OfferManager::COLUMN_TITLE] = $this->getParameter(OfferManager::COLUMN_TITLE);
        $params["max".OfferManager::COLUMN_PRICE] = floatval($this->getParameter("max".OfferManager::COLUMN_PRICE));
        $params["min".OfferManager::COLUMN_PRICE] = floatval($this->getParameter("min".OfferManager::COLUMN_PRICE));
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
        if (!empty($user)){
            $goodsInTheCategory = $this->filterService->getOffersBydistance( $userLatitude, $userLongitude,$params, $paginator->getLength(),$paginator->getOffset());
        }else{
              $goodsInTheCategory = $this->filterService->getOffersByParams($params, $paginator->getLength(),$paginator->getOffset());
         }
        $offerCount = !empty($user) ? $this->filterService->getCountOffersByDistance($this->filterService->getCountOffersByParams($params ),$userLatitude, $userLongitude , $params) : $this->filterService->getCountOffersByParams($params ); 
	$paginator->setItemCount($offerCount);
        $this->template->noItems = $offerCount == 0;
        $this->template->paginator = $paginator;
        $this->template->allOffers = $goodsInTheCategory;
        $this->template->noCategory = empty($kategorie);
        $urlParameters = $this->getRequest()->getParameters();
        unset($urlParameters["page"]);
        unset($urlParameters["locale"]);
        unset($urlParameters["search"]);
        unset($urlParameters["do"]);
        $this->template->urlParameters = $urlParameters;
	$this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->image = OfferManager::COLUMN_MAIN_PHOTO;
        $this->template->id = OfferManager::COLUMN_ID;
        $this->template->offerOwner = UserManager::COLUMN_NAME;
        $this->template->userTable = UserManager::TABLE_NAME;
        $this->template->folderForOfferPictures = $this->photoManager->FOLDER_FOR_OFFER_PICTURES;
        $this->template->folderForCategoryPictures = $this->photoManager->FOLDER_FOR_CATEGORY_PICTURES;
        $mainCategories = $this->categoryManager->getSubcategories(empty($kategorie) ? null : $kategorie);
        $this->template->categories = $this->filterService->getActiveSubcategories(empty($params) ? null : $params);
        $this->template->categoryId = CategoryManager::COLUMN_ID; 
        $this->template->categoryTitle = CategoryManager::COLUMN_TITLE;
        $this->template->categoryImage = CategoryManager::COLUMN_IMAGE;
        $this->template->parentCategories = $this->categoryManager->breadcrumb($kategorie);
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
        
        public function createComponentFilterForm($parameters){
        if($this->getUser()->isLoggedIn()){
            $user = $this->userManager->get($this->getUser()->id);
            $userLatitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LATITUDE};
            $userLongitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LONGITUDE};
        }
        
        $params = $this->getRequest()->getParameters();
        if (!empty($this->getParameter("distance"))){
            $params['distance']=floatval($this->getParameter("distance")); 
            $distanceLatitude = 0.009*floatval($this->getParameter("distance"));
            $params["max".AddressManager::COLUMN_LATITUDE]= $userLatitude+$distanceLatitude;
            $params["min".AddressManager::COLUMN_LATITUDE]= $userLatitude-$distanceLatitude;
            $distanceLongitude = 0.00898*floatval($this->getParameter("distance"));
            $params["max".AddressManager::COLUMN_LONGITUDE]= $userLongitude+$distanceLongitude;
            $params["min".AddressManager::COLUMN_LONGITUDE]= $userLongitude-$distanceLongitude;
        }
        $user = $this->getUser()->isLoggedIn() ? $this->userManager->get($this->getUser()->id) : null;
        $form = $this->offerFormFactory->createFilterForm($params, $user);
        $form->setDefaults($params);
        $form->setMethod("GET");
        
        $form->onSuccess[] = function(Form $form, $values){
            $noError = true;
            if($form->values["max".OfferManager::COLUMN_PRICE] < $form->values["min".OfferManager::COLUMN_PRICE] && !empty($form->values["min".OfferManager::COLUMN_PRICE])){
                $form->addError("Horní hranice ceny nemůže být nižší než spodní hranice.");
                $noError = false;
            }
           
        };
        return $form;
    }
	
	public function renderUsermanagement(){
		$this->template->userId = UserManager::COLUMN_ID;
		$this->template->username = UserManager::COLUMN_NAME;
		if($this->getUser()->id !== null){
			$users = $this->userManager->getAll();
			$usersForTemplate = array();
			foreach($users as $user){
				if($user[UserManager::COLUMN_ID] != $this->getUser()->id){
					array_push($usersForTemplate, $user);
				}
			}
			$this->template->users = $usersForTemplate;
		} else {
			$this->template->users = $this->userManager->getAll();
		}
	}
        
         protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->formPathInLine = __DIR__ . '/templates/formInLine.latte'; // Předá cestu ke globální šabloně formulářů do šablony.
       
    }
}
