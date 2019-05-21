<?php

namespace App\Presenters;


use App\Forms\OfferFormFactory;
use App\Model\BaseManager;
use App\Model\CategoryManager;
use App\Model\OfferManager;
use App\Model\UserManager;
use App\Model\CityManager;
use App\Model\CommentManager;
use Nette\Forms\Form;

class HomepagePresenter extends BasePresenter
{

    /** @var  UserManager */
    private $userManager;
    /** @var  OfferFormFactory */
    private $offerFormFactory;
    /** @var  CategoryManager */
    private $categoryManager;
    /** @var  OfferManager */
    private $offerManager;
	/** @var CityManager */
	private $cityManager;
	/** @var CommentManager */
	private $commentManager;

    public function __construct(UserManager $userManager, OfferFormFactory $offerFormFactory,
    CategoryManager $categoryManager, OfferManager $offerManager, CityManager $cityManager, CommentManager $commentManager)
    {
        $this->userManager = $userManager;
        $this->offerFormFactory = $offerFormFactory;
        $this->categoryManager = $categoryManager;
        $this->offerManager = $offerManager;
		$this->cityManager = $cityManager;
		$this->commentManager = $commentManager;
    }

    public function createComponentSearchForm(){
        $form = $this->offerFormFactory->createSearchForm();
        $form->onSuccess[] = function(Form $form, $values){
            $this->redirect(302, "Offer:search", ["text" => $values["text"]]);
        };
        return $form;
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

    public function createComponentFilterForm(){
        $form = $this->offerFormFactory->createFilterForm();
        $cityArray = [BaseManager::CITY];
        $form->setDefaults(["cities" => $cityArray]);
        $form->onSuccess[] = function(Form $form, $values){
            $cities = $form->getValues("cities");
            $noError = true;
            if(empty($cities["cities"]) || count($cities["cities"]) == 0){
                $form->addError("Prosím zvolte alespoň jednu obec.");
                $noError = false;
            }
            if($form->values["priceTo"] < $form->values["priceFrom"]){
                $form->addError("Horní hranice ceny nemůže být nižší než spodní hranice.");
                $noError = false;
            }
            if($noError === true){
                $cityArray = $values["cities"];
                $priceFrom = $values["priceFrom"];
                $priceTo = $values["priceTo"];
                $cityArrayString = "";
                $first = true;
                foreach($cityArray as $city){
                    $character = $first === true ? "" : ",";
                    $cityArrayString.=$character.$city;
                    $first = false;
                }
                $this->redirect(303, "Offer:list", ["cityArray" => $cityArrayString,
                    "priceFrom" => $priceFrom, "priceTo" => $priceTo]);
            }
        };
        return $form;
    }

    public function renderDefault($page = 1)
	{
        $_SESSION["fileArray"] = [];
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
		$allOffers = $this->offerManager->getAll();
		$offerCount = $allOffers->count();
		$this->template->noItems = $offerCount == 0;
		$lastPage = ($offerCount % BaseManager::PAGE_SIZE == 0) ? intval($offerCount/BaseManager::PAGE_SIZE) : intval($offerCount/BaseManager::PAGE_SIZE) + 1;
		$offersForTemplate = $this->offerManager->getOffersOnPage($allOffers, $page);
		$canShuffle = shuffle($offersForTemplate);
		$this->template->offers = $offersForTemplate;
        $this->template->page = $page;
        $this->template->lastPage = $lastPage;
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
        $mainCategories = $this->categoryManager->getSubcategories(null);
        $categories = array();
        foreach($mainCategories as $mainCategory){
            $id = $mainCategory[CategoryManager::COLUMN_ID];
            $title = $mainCategory[CategoryManager::COLUMN_TITLE];
            $image = $mainCategory[CategoryManager::COLUMN_IMAGE];
            $countOffers = $this->offerManager->getCountOffersByCitiesAndCriterionWithSubcategories($id,[BaseManager::CITY]);
            $categories[$id] = ["title" => $title, "countOffers" => $countOffers, "image" => $image];
        }
        $this->template->categories = $categories;
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
}
