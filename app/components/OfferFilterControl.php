<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components;

use Nette\Application\UI\Control;
use Nette\Database\Table\IRow;
use App\Model\OfferManager;
use App\Model\PhotoManager;
use App\Model\UserManager;
use App\Model\AddressManager;
use App\Model\CategoryManager;
use Nette\Utils\Paginator;
use App\Services\FilterService;

/**
 * Description of OfferFilterControl
 *
 * @author Marcel
 */
class OfferFilterControl extends Control {
   
    const TEMPLATE = __DIR__ . '/templates/offerFilter.latte'; 
    
    private UserManager $userManager;

    private OfferManager $offerManager;
    
    private CategoryManager $categoryManager;
    
    private $filterService;
    
    public function __construct(UserManager $userManager, OfferManager $offerManager, CategoryManager $categoryManager, FilterService $filterService)
   {
       $this->userManager = $userManager;
       $this->offerManager = $offerManager;
       $this->categoryManager = $categoryManager;
       $this->filterService = $filterService;
   }
   
   public function setFilter($user = null, $parametres = null)
   {
       if ($user){
            $this->offerId = $offer->{PhotoManager::COLUMN_ID};
       }else{
            $this->offerId = null;     
       }
   }
   
    public function render()
    {
        $this->template->setFile(self::TEMPLATE); // Nastaví šablonu komponenty.
        $this->template->loggedIn = $this->getPresenter()->getUser()->id !== null;
        if($this->getPresenter()->getUser()->id !== null){
            $identity = $this->getPresenter()->getUser()->id;
            $user = $this->userManager->get($identity);
            $userName = $user[UserManager::COLUMN_NAME];
            $userLatitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LATITUDE};
            $userLongitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LONGITUDE};
        }else{
            $userName = "";
        }
        
        $categoryID = $this->getPresenter()->getParameter("categoryID");
        
        $this->template->username = $userName;
        $params = [];
        $mainCategoryIds = $this->categoryManager->getSubcategoryIds($categoryID);
        if (empty($mainCategoryIds)){
            $mainCategoryIds = $categoryID; 
        }
        $params[OfferManager::COLUMN_CATEGORY] = $mainCategoryIds;
        $params[OfferManager::COLUMN_TITLE] = $this->getPresenter()->getParameter("titleText");
        $params["max".OfferManager::COLUMN_PRICE] = floatval($this->getPresenter()->getParameter("priceTo"));
        $params["min".OfferManager::COLUMN_PRICE] = floatval($this->getPresenter()->getParameter("priceFrom"));
        if ($this->getPresenter()->getParameter("distance")){
            $params['distance']=floatval($this->getPresenter()->getParameter("distance")); 
            $distanceLatitude = 0.009*floatval($this->getPresenter()->getParameter("distance"));
            $params["max".AddressManager::COLUMN_LATITUDE]= $userLatitude+$distanceLatitude;
            $params["min".AddressManager::COLUMN_LATITUDE]= $userLatitude-$distanceLatitude;
            $distanceLongitude = 0.00898*floatval($this->getPresenter()->getParameter("distance"));
            $params["max".AddressManager::COLUMN_LONGITUDE]= $userLongitude+$distanceLongitude;
            $params["min".AddressManager::COLUMN_LONGITUDE]= $userLongitude-$distanceLongitude;
        }
        
	$paginator = new Paginator;
	
        $page = intval($this->getPresenter()->getParameter('page'));
        
	$paginator->setItemsPerPage(10); // počet položek na stránce
	$paginator->setPage($page); // číslo aktuální stránky
        $offerCount = !empty($user) ? $this->filterService->getCountOffersByDistance($this->filterService->getCountOffersByParams($params ),$userLatitude, $userLongitude , $params) : $this->filterService->getCountOffersByParams($params ); 
	$paginator->setItemCount($offerCount);
        
        $this->template->paginator = $paginator;
        if (!empty($user)){
             $goodsInTheCategory = $this->filterService->getOffersBydistance( $userLatitude, $userLongitude,$params, $paginator->getLength(),$paginator->getOffset());
        }else{
              $goodsInTheCategory = $this->filterService->getOffersByParams($params, $paginator->getLength(),$paginator->getOffset());
         }     
	/* $offerCount = count($goodsInTheCategory);
        $paginator->setItemCount($offerCount); // celkový počet článků */
        //$goodsInTheCategory = $this->offerManager->getOffersByParams($params, $paginator->getLength(), $paginator->getOffset());
	$mainCategories = $this->categoryManager->getSubcategories($categoryID == "all" ? null : $categoryID);
			
        $this->template->offers = $goodsInTheCategory;
        $this->template->offerID = OfferManager::COLUMN_ID;
        $this->template->noCategory = ($categoryID === null || $categoryID === "all");
        $this->template->noItems = empty($goodsInTheCategory);
        
        $urlParameters = $this->getPresenter()->getRequest()->getParameters();
        unset($urlParameters["page"]);
        unset($urlParameters["locale"]);
        unset($urlParameters["search"]);
        unset($urlParameters["do"]);
        $this->template->urlParameters = $urlParameters;
        
        $this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->image = OfferManager::COLUMN_MAIN_PHOTO;
        $this->template->offerOwner = UserManager::TABLE_NAME;
        $this->template->offerOwnerName = UserManager::COLUMN_NAME;
        $this->template->title = OfferManager::COLUMN_TITLE;
        $this->template->price = OfferManager::COLUMN_PRICE;
        $this->template->image = PhotoManager::TABLE_NAME;
        
        $this->template->photoPath = PhotoManager::COLUMN_PATH;
        $this->template->render();
    }
}
