<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

use App\Model\CategoryManager;
use App\Model\OfferManager;
use App\Model\UserManager;
use App\Model\AddressManager;
use App\Model\PhotoManager;
use Nette\Localization\ITranslator;
use Nette\Database\Explorer;
/**
 * Description of FilterService
 *
 * @author Marcel
 */
class FilterService {
    private $categoryManager;
    private $offerManager;
    private $userManager;
    private $addressManager;
    private $photoManager;
    protected $translator;
     /** @var Nette\Database\Explorer */
    protected $database;
    
    public function __construct(CategoryManager $categoryManager, OfferManager $offerManager,
                                UserManager $userManager, AddressManager $addressManager, PhotoManager $photoManager, ITranslator $translator, Explorer $database  ) {
        $this->categoryManager = $categoryManager;
        $this->offerManager = $offerManager;
        $this->userManager = $userManager;
        $this->addressManager = $addressManager;
        $this->photoManager = $photoManager;
        $this->translator = $translator;
        $this->database = $database;
    }
    
    public function getCategoriesForSelect($params = null, $latitude2 = null, $longitude2 = null) {
        $array = [0 =>$this->translator->translate('messages.FilterForm.allCategories')];
        if(!empty($params[OfferManager::COLUMN_CATEGORY])){
                    $categories = $this->categoryManager->getSubcategoriesWithCategory($params[OfferManager::COLUMN_CATEGORY]);
                    if(!empty($categories)){
                        array_push($categories, $this->categoryManager->get($params[OfferManager::COLUMN_CATEGORY])); 
                    }
               } else{
                    $categories = $this->categoryManager->getAll();
               }
        foreach ($categories as $category){
            $subcategories = $this->categoryManager->getSubcategories($category->{CategoryManager::COLUMN_ID});
            $categoryCount = 0;
            $arr = [];
            $translatedCategoryTitle = $this->translator->translate('messages.category.'.trim($category->{CategoryManager::COLUMN_TITLE}));

            foreach ($subcategories as $subcategory){
                $subcategoryCount = $this->getCountByDistance($params, $latitude2, $longitude2, $subcategory->{CategoryManager::COLUMN_ID});
                $translatedSubcategoryTitle = $this->translator->translate('messages.category.'.trim($subcategory->{CategoryManager::COLUMN_TITLE}));
                if ($subcategoryCount > 0){
                    $arr[$subcategory->{CategoryManager::COLUMN_ID}] = "...".$translatedSubcategoryTitle."  (".$subcategoryCount.")";
                    $categoryCount += $subcategoryCount;
                }
            }
            if ($categoryCount > 0 || (count($categories) == 2 )){
                if (count($categories) == 2) {
                    $categoryCount = $this->getCountByDistance($params, $latitude2, $longitude2, $category->{CategoryManager::COLUMN_ID});
                }
                $array[$category->{CategoryManager::COLUMN_ID}] = $translatedCategoryTitle."  (".$categoryCount.")";

                foreach ($arr as $subcategoryId => $subcategory){
                    $array[$subcategoryId] = $subcategory;
                }
            }
        }

        return $array;
    }

   
   private function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'kilometers') {
        $theta = $longitude1 - $longitude2; 
        $distance = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta))); 
        $distance = acos($distance); 
        $distance = rad2deg($distance); 
        $distance = $distance * 60 * 1.1515; 
        switch($unit) { 
          case 'miles': 
            break; 
          case 'kilometers' : 
            $distance = $distance * 1.609344; 
        } 
        return (round($distance,2)); 
    }
   
    public function getCountByDistance($params = null, $latitude2 = null, $longitude2 = null, $categoryId = null) {
         if (!empty($params["min".AddressManager::COLUMN_LATITUDE])&&!empty($params["max".AddressManager::COLUMN_LATITUDE])&&!empty($params["min".AddressManager::COLUMN_LONGITUDE])&&!empty($params["max".AddressManager::COLUMN_LONGITUDE])){
            $count = $this->offerManager->getCountOffers($params, $categoryId);
            $offers = $this->offerManager->getOffersTable($params, $categoryId);
            foreach ($offers as $key => $offer){
                $user = $this->userManager->get($offer[OfferManager::COLUMN_USER]);  
                $address = $this->addressManager->get($user->{UserManager::COLUMN_ACTIVE_ADDRESS_ID}); 
                $longitude1 = $address->{AddressManager::COLUMN_LONGITUDE}; 
                $latitude1 = $address->{AddressManager::COLUMN_LATITUDE};
                $distance = $this->addressManager->getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2);
                if($params['distance']<$distance){
                    $count = $count - 1;
                }
            }
            return $count;
        } else {
            return $this->offerManager->getCountOffers($params, $categoryId);
        }
    }
    
    public function getActiveSubcategoriesBySeller($parameters = null) {
        // Základní dotaz
        $query = $this->database->table(CategoryManager::TABLE_NAME);

        // Vytvoření subdotazu pro výběr ID kategorií z tabulky 'offer'
        $subquery = $this->database->table(OfferManager::TABLE_NAME)
            ->select(OfferManager::COLUMN_CATEGORY)
            ->group(OfferManager::COLUMN_CATEGORY);
        
        // Pokud je nastavena kategorie, přidáme podmínku na výběr subkategorií
        if (isset($parameters['category'])) {
            $query->where(CategoryManager::COLUMN_PARENT_CATEGORY, intval($parameters['category']));
        }

        // Pokud je nastaveno jméno prodejce (seller_name), přidáme podmínku na výběr dle uživatele
        if (isset($parameters['seller_name'])) {
            $subquery->where(OfferManager::COLUMN_USER, $parameters['seller_name']);
        }

        // Aplikace subdotazu na hlavní dotaz
        $query->where(CategoryManager::COLUMN_ID, $subquery->fetchAssoc("category"));

        return $query->fetchAll();
    }
    
    public function getActiveSubcategories($parameters = null) {
        $allCategories = $this->getcountWithSubcategories($parameters);
        $ActiveSubcategories = [];
        foreach ($allCategories as $category) {
            $activeCategories = [];
            if(isset($category['countOffers']) && $category['countOffers'] == 0 ){
                continue;
            } else{
                foreach ($category as $key => $active){
                    if(isset($active['countOffers']) && $active['countOffers'] == 0){
                        continue;
                    } else if (!isset($active['countOffers'])) {
                        $activeCategories[$key] = $active;
                    } else {
                        array_push($ActiveSubcategories, $active);
                        array_push($activeCategories,$active);
                    }
                }
            }
            array_push($ActiveSubcategories, $activeCategories);
        }
        return $ActiveSubcategories;
    }
    
    public function getcountWithSubcategories($parameters = null) {
        $subcategories = $this->categoryManager->getSubcategories(null);   
        $allCategories = [];
        foreach ($subcategories as $subcategory) {
            $categories = $this->categoryManager->getSubcategories($subcategory->{CategoryManager::COLUMN_ID});
            $countOffers = $this->offerManager->getCountOffers($parameters, $subcategory->{CategoryManager::COLUMN_ID});
            $pureSubcategory = $subcategory->toArray();
            $pureSubcategory['countOffers'] = $countOffers ;
          if(!empty($categories)){
                $count = 0;
                foreach ($categories as $category){
                $countOffer = $this->offerManager->getCountOffers($parameters, intval($category['id']));
                $category = $category->toArray();
                $count = $count + $countOffer;
                $category['countOffers'] = $countOffer;
                array_push($pureSubcategory,$category);
                }
            }
            $pureSubcategory['countOffers'] =!empty($count) ? $countOffers + $count : $countOffers;
            array_push($allCategories,$pureSubcategory);
        }
        return $allCategories;
    }
    
    public function getOffersBydistance($latitude2,$longitude2,$params = null,int $limit = null, int $offset = null) {
        if (!empty($params["min".AddressManager::COLUMN_LATITUDE])&&!empty($params["max".AddressManager::COLUMN_LATITUDE])&&!empty($params["min".AddressManager::COLUMN_LONGITUDE])&&!empty($params["max".AddressManager::COLUMN_LONGITUDE])){
            $offers = $this->getOffersByParams($params, $limit, $offset);
           foreach ($offers as $key => $offer){
              $user = $this->userManager->get($offer[OfferManager::COLUMN_USER]);  
              $address = $this->addressManager->get($user->{UserManager::COLUMN_ACTIVE_ADDRESS_ID}); 
              $longitude1 = $address->{AddressManager::COLUMN_LONGITUDE}; 
              $latitude1 = $address->{AddressManager::COLUMN_LATITUDE};
              $distance = $this->getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2);
              if($params['distance']<$distance){
                  continue;
              }
              $offers[$key]['distance'] = $distance;
              }
            return $offers;
        } else {
            $offers = $this->getOffersByParams($params, $limit, $offset);
            foreach ($offers as $key => $offer){
              $user = $this->userManager->get($offer[OfferManager::COLUMN_USER]);  
              $address = $this->addressManager->get($user->{UserManager::COLUMN_ACTIVE_ADDRESS_ID});
              $longitude1 = $address->{AddressManager::COLUMN_LONGITUDE}; 
              $latitude1 = $address->{AddressManager::COLUMN_LATITUDE};
              $distance = $this->addressManager->getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2);
              $offers[$key]['distance'] = $distance;
             }
            return $offers;
            
        }
    }
    
    public function getOffersByParams($params = null, int $limit = null, int $offset = null) {
       $offers = $this->offerManager->getOfferTableByParams($params)->limit($limit, $offset)->fetchAll();
       $pureOffers = [];
        foreach ($offers as $offer) {
                $pure = $offer->toArray();
                $pure['photoPath'] = $offer->{PhotoManager::TABLE_NAME}->{PhotoManager::COLUMN_PATH};
                $pure['offerOwnerName']= $offer->{UserManager::TABLE_NAME}->{UserManager::COLUMN_NAME};
                array_push($pureOffers, $pure);
        }
        return $pureOffers;
    }
    
    public function getCountOffersByDistance($count, $latitude2, $longitude2, $params = null) {
    if (!empty($params["min".AddressManager::COLUMN_LATITUDE])&&!empty($params["max".AddressManager::COLUMN_LATITUDE])&&!empty($params["min".AddressManager::COLUMN_LONGITUDE])&&!empty($params["max".AddressManager::COLUMN_LONGITUDE])){
            $offers = $this->getOffersByParams($params);
            foreach ($offers as $key => $offer){
              $user = $this->userManager->get($offer[OfferManager::COLUMN_USER]);  
              $address = $this->addressManager->get($user->{UserManager::COLUMN_ACTIVE_ADDRESS_ID}); 
              $longitude1 = $address->{AddressManager::COLUMN_LONGITUDE}; 
              $latitude1 = $address->{AddressManager::COLUMN_LATITUDE};
              $distance = $this->getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2);
              if($params['distance']<$distance){
                  $count=$count - 1;
              }
            }
            return $count;
        } else {
            return $this->getCountOffersByParams($params );
             }
    }
    
    public function getCountOffersByParams($params = null) {
        $table = $this->offerManager->getOfferTableByParams($params);
        return $table->count('*');
    }
    
    public function getDistinctSellerNames() {
        $uniqueUserIds = $this->database->table(OfferManager::TABLE_NAME)
                            ->select(OfferManager::COLUMN_USER)
                            ->group(OfferManager::COLUMN_USER)
                            ->fetchPairs(OfferManager::COLUMN_USER, OfferManager::COLUMN_USER);
        $sellerNames = [$this->translator->translate('messages.FilterForm.all')];

        foreach($uniqueUserIds as $userId) {
            $user = $this->database->table(UserManager::TABLE_NAME)
                        ->get($userId);

            if($user !== null) {
                $sellerNames[$user[UserManager::COLUMN_ID]] = $user[UserManager::COLUMN_NAME];
            }
        }

        return $sellerNames;
    }

}
