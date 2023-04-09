<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 22:21
 */

namespace App\Forms;

use App\Model;
use Nette\Forms\Form;
use App\Services\FilterService;



class OfferFormFactory extends FormFactory
{
    /** @var  Model\CityManager */
    private $cityManager;
    /**  @var Model\OfferManager*/
    private $offerManager;
    /**  @var Model\CategoryManager*/
    private $categoryManager;
    /** @var  Model\PhotoManager */
    private $photoManager;
    
    private $filterService;

    private $user;
    private $offer;

    public function __construct(Model\CityManager $cityManager,
                                Model\OfferManager $offerManager,
                                Model\CategoryManager $categoryManager,
                                Model\PhotoManager $photoManager,
                                FilterService $filterService){
        $this->cityManager = $cityManager;
        $this->offerManager = $offerManager;
        $this->categoryManager = $categoryManager;
        $this->photoManager = $photoManager;
        $this->filterService = $filterService;
        $this->user = null;
        $this->offer = null;
    }

    public function createCategoriesForForm(){
        $categories = $this->categoryManager->getAll();
        $categoryArray = array();
        foreach($categories as $category) {
            $id = $category[Model\CategoryManager::COLUMN_ID];
            $parentCategoryID = $category[Model\CategoryManager::COLUMN_PARENT_CATEGORY];
            $parentCategoryTitle = "";
            if ($parentCategoryID != null) {
                $parentCategory = $this->categoryManager->get($parentCategoryID);
                $parentCategoryTitle = $parentCategory[Model\CategoryManager::COLUMN_TITLE] . " >> ";
                $categoryArray[$id] = $parentCategoryTitle . $category[Model\CategoryManager::COLUMN_TITLE];
            }
        }
        return $categoryArray;
    }

    public function createForm($user){
        $this->user = $user;
        $form = $this->create();
        $form->addText('title', 'Název')
            ->setRequired(true)->addRule(Form::MAX_LENGTH, "Název musí být dlouhý nejvýše 65 znaků.", 65);
        $form->addInteger('price', 'Cena')
            ->setRequired(true);
        $form->addTextArea('description', 'Popis');
        $form->addSelect('category', 'Kategorie')->setRequired(true)->setHtmlAttribute("class", "form-control");
        $form->addMultiUpload('photos', "Fotografie")
             ->setHtmlAttribute("id", "fileupload")
             ->setHtmlAttribute("class", "form-control-file")
             ->setHtmlAttribute("onchange", "uploadPhotos()");
        $form->addSubmit('addOffer', 'Umístit nabídku');
        $form["category"]->setItems($this->createCategoriesForForm());
        return $form;
    }

    public function createEditForm($id, $user){
        $this->offer = $id;
        $this->user = $user;
        $form = $this->create();
        $form->addText('title', 'Název')
            ->setRequired(true)->addRule(Form::MAX_LENGTH, "Název musí být dlouhý nejvýše 65 znaků.", 65);
        $form->addInteger('price', 'Cena')
            ->setRequired(true);
        $form->addTextArea('description', 'Popis');
        $form->addSelect('category', 'Kategorie')->setRequired(true);
        $form->addMultiUpload('photos', "Fotografie");
        $form->addSubmit('addOffer', 'Editovat nabídku');
        $form["category"]->setItems($this->createCategoriesForForm());
        $offer = $this->offerManager->get(intval($id));
        $form->setDefaults(["title" => $offer[Model\OfferManager::COLUMN_TITLE],
        "price" => $offer[Model\OfferManager::COLUMN_PRICE],
        "description" => $offer[Model\OfferManager::COLUMN_DESCRIPTION],
            "category" => $offer[Model\OfferManager::COLUMN_CATEGORY]]);
        return $form;
    }

    public function getCities(){
        $cities = $this->cityManager->getAll();
        $citiesForForm = array();
        foreach($cities as $city){
            $id = $city[Model\CityManager::COLUMN_ID];
            $name = $city[Model\CityManager::COLUMN_TITLE];
            $citiesForForm[$id] = $name;
        }
        return $citiesForForm;
    }

    public function createFilterForm($parameters,$user = null){
        $form = $this->create();
        if(!empty($user)){
            $latitude2 = $user->active_address->{Model\AddressManager::COLUMN_LATITUDE};
            $longitude2 = $user->active_address->{Model\AddressManager::COLUMN_LONGITUDE};
            $categories = $this->filterService->getCategoriesForSelect($parameters, $latitude2 , $longitude2);
        }else{
             $categories = $this->filterService->getCategoriesForSelect($parameters);
        }
        $form->addSelect(Model\OfferManager::COLUMN_CATEGORY, 'V kategorii:', $categories);
        $form->addText(Model\OfferManager::COLUMN_TITLE , 'text v titulku');
        $form->addInteger('min'.Model\OfferManager::COLUMN_PRICE, "Cena od");
        $form->addInteger('max'.Model\OfferManager::COLUMN_PRICE, 'Cena do');
        if(!empty($user)){
            if (!empty($parameters['distance'])){
                $form->addInteger('distance', 'V mém okolí do tohoto počtu km')
                     ->setDefaultValue($parameters['distance']) ;  
            }else{
                 $form->addInteger('distance', 'V mém okolí do tohoto počtu km');
             }
        }     
        $form->addSubmit('search', 'Vyhledat nabídky');
        return $form;
    }

    public function createSearchForm(){
        $form = $this->create();
        $form->addText("text","");
        $form->addSubmit("search", "Vyhledat");
        return $form;
    }
}