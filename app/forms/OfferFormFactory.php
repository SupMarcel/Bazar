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

class OfferFormFactory
{
    /** @var FormFactory */
    private $factory;
    /** @var  Model\CityManager */
    private $cityManager;
    /**  @var Model\OfferManager*/
    private $offerManager;
    /**  @var Model\CategoryManager*/
    private $categoryManager;
    /** @var  Model\PhotoManager */
    private $photoManager;

    private $user;
    private $offer;

    public function __construct(FormFactory $factory,
                                Model\CityManager $cityManager,
                                Model\OfferManager $offerManager,
                                Model\CategoryManager $categoryManager,
Model\PhotoManager $photoManager){
        $this->factory = $factory;
        $this->cityManager = $cityManager;
        $this->offerManager = $offerManager;
        $this->categoryManager = $categoryManager;
        $this->photoManager = $photoManager;
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
        $form = $this->factory->create();
        $form->addText('title', 'Název')
            ->setRequired(true)->addRule(Form::MAX_LENGTH, "Název musí být dlouhý nejvýše 65 znaků.", 65);
        $form->addInteger('price', 'Cena')
            ->setRequired(true);
        $form->addTextArea('description', 'Popis');
        $form->addSelect('category', 'Kategorie')->setRequired(true)->setHtmlAttribute("class", "form-control");
        $form->addMultiUpload('photos', "Fotografie")->setRequired(true)->
        setHtmlAttribute("class", "form-control-file")->
        addRule(Form::MIN_LENGTH,
                "Je potřeba nahrát alespoň jeden soubor. Jeden ze souborů bude použit jako hlavní fotografie.",
                1);
        $form->addSubmit('addOffer', 'Umístit nabídku');
        $form["category"]->setItems($this->createCategoriesForForm());
        return $form;
    }

    public function createEditForm($id, $user){
        $this->offer = $id;
        $this->user = $user;
        $form = $this->factory->create();
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

    public function createFilterForm(){
        $form = $this->factory->create();
        $form->addInteger('priceFrom', "Cena od");
        $form->addInteger('priceTo', 'Cena do');
        $form->addCheckboxList('cities', '', $this->getCities())->addCondition(Form::NOT_EQUAL, []);
        $form->addSubmit('search', 'Vyhledat nabídky');
        return $form;
    }

    public function createSearchForm(){
        $form = $this->factory->create();
        $form->addText("text","");
        $form->addSubmit("search", "Vyhledat");
        return $form;
    }
}