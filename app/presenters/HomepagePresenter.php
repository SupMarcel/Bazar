<?php

namespace App\Presenters;

use App\Forms\OfferFormFactory;
use App\Model\BaseManager;
use App\Model\CategoryManager;
use App\Model\OfferManager;
use App\Model\UserManager;
use App\Model\AddressManager;
use App\Model\CommentManager;
use Nette\Forms\Form;
use App\Model\PhotoManager;
use App\Services\FilterService;
use Nette\Utils\Paginator;
use App\Control\SubcategoriesControl;
use App\Control\BreadcrumbControl;

class HomepagePresenter extends BasePresenter {

    /** @var  OfferFormFactory */
    private $offerFormFactory;

    /** @var  CategoryManager */
    private $categoryManager;

    /** @var  OfferManager */
    private $offerManager;
    private $photoManager;

    /** @var CommentManager */
    private $commentManager;
    private $filterService;

    public function __construct(OfferFormFactory $offerFormFactory,
            CategoryManager $categoryManager, OfferManager $offerManager, PhotoManager $photoManager, CommentManager $commentManager, FilterService $filterService) {
        $this->offerFormFactory = $offerFormFactory;
        $this->categoryManager = $categoryManager;
        $this->offerManager = $offerManager;
        $this->photoManager = $photoManager;
        $this->commentManager = $commentManager;
        $this->filterService = $filterService;
    }

    public function actionRemoveuser($id) {
        $this->offerManager->removeOffersByUser(intval($id));
        $this->commentManager->removeCommentsByUser(intval($id));
        $this->userManager->remove(intval($id));
        $this->redirect(302, "Homepage:default");
    }

    public function actionCancelEmailSubscription($userId) {
        if (!empty($userId)) {
            $this->userManager->cancelEmailSubscription($userId);
        }
        $this->redirect(302, "Homepage:default");
    }

    private function calculateMinMaxCoordinates($distance, $userLatitude, $userLongitude) {
        $distanceLatitude = 0.009 * floatval($distance);
        $distanceLongitude = 0.00898 * floatval($distance);

        return [
            "max" . AddressManager::COLUMN_LATITUDE => $userLatitude + $distanceLatitude,
            "min" . AddressManager::COLUMN_LATITUDE => $userLatitude - $distanceLatitude,
            "max" . AddressManager::COLUMN_LONGITUDE => $userLongitude + $distanceLongitude,
            "min" . AddressManager::COLUMN_LONGITUDE => $userLongitude - $distanceLongitude,
        ];
    }

    public function renderDefault($page = 1, $category = null) {
        $_SESSION["fileArray"] = [];

        if ($this->getUser()->isLoggedIn()) {
            $user = $this->userManager->get($this->getUser()->id);
            $userLatitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LATITUDE};
            $userLongitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LONGITUDE};
        }
        $params = [];
        if (empty(floatval($this->getParameter(OfferManager::COLUMN_CATEGORY)))) {
            $params[OfferManager::COLUMN_CATEGORY] = null;
        } else {
            $params[OfferManager::COLUMN_CATEGORY] = floatval($this->getParameter(OfferManager::COLUMN_CATEGORY));
        }
        $params[CategoryManager::COLUMN_PARENT_CATEGORY] = $category;
        $params[OfferManager::COLUMN_TITLE] = $this->getParameter(OfferManager::COLUMN_TITLE);
        $params["max" . OfferManager::COLUMN_PRICE] = floatval($this->getParameter("max" . OfferManager::COLUMN_PRICE));
        $params["min" . OfferManager::COLUMN_PRICE] = floatval($this->getParameter("min" . OfferManager::COLUMN_PRICE));
        if ($this->getParameter("distance")) {
            $params['distance'] = floatval($this->getParameter("distance"));
            $coordinates = $this->calculateMinMaxCoordinates($params['distance'], $userLatitude, $userLongitude);
            $params = array_merge($params, $coordinates);
        }
        $paginator = new Paginator;
        $paginator->setItemsPerPage(10); // počet položek na stránce
        $paginator->setPage($page); // číslo aktuální stránky
        if (!empty($user)) {
            $goodsInTheCategory = $this->filterService->getOffersBydistance($userLatitude, $userLongitude, $params, $paginator->getLength(), $paginator->getOffset());
        } else {
            $goodsInTheCategory = $this->filterService->getOffersByParams($params, $paginator->getLength(), $paginator->getOffset());
        }
        $offerCount = !empty($user) ? $this->filterService->getCountOffersByDistance($this->filterService->getCountOffersByParams($params), $userLatitude, $userLongitude, $params) : $this->filterService->getCountOffersByParams($params);
        $paginator->setItemCount($offerCount);
        $this->template->noItems = $offerCount == 0;
        $this->template->paginator = $paginator;
        $this->template->allOffers = $goodsInTheCategory;
        $urlParameters = $this->getRequest()->getParameters();
        unset($urlParameters["page"]);
        unset($urlParameters["locale"]);
        unset($urlParameters["search"]);
        unset($urlParameters["do"]);
        $this->template->urlParameters = $urlParameters;
        $this->template->folderForOfferPictures = $this->photoManager->FOLDER_FOR_OFFER_PICTURES;
        $this->template->parentCategories = $this->categoryManager->breadcrumb($category);
    }
    
     public function renderDetail($id){
        $_SESSION["fileArray"] = [];
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
        }
        $offer = $this->offerManager->get(intval($id));
        $this->template->comment = null;
        $this->template->offer = $offer;
        $authorID = $offer[OfferManager::COLUMN_USER];
        $this->template->author = $this->userManager->get($authorID);
        $this->template->comments = $this->commentManager->getCommentsByOffer(intval($id));
        $this->template->commentID = CommentManager::COLUMN_ID;
        $this->template->images = $this->photoManager->getPhotosByOffer(intval($id));
    }
    

    public function createComponentFilterForm() {
        if ($this->getUser()->isLoggedIn()) {
            $user = $this->userManager->get($this->getUser()->id);
            $userLatitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LATITUDE};
            $userLongitude = $user->{AddressManager::TABLE_NAME}->{AddressManager::COLUMN_LONGITUDE};
        } else {
            $userLatitude = null;
            $userLongitude = null;
        }

        $params = $this->getRequest()->getParameters();
        if (!empty($this->getParameter("distance"))) {
            $params['distance'] = floatval($this->getParameter("distance"));
            $coordinates = $this->calculateMinMaxCoordinates($params['distance'], $userLatitude, $userLongitude);
            $params = array_merge($params, $coordinates);
        }
        $user = $this->getUser()->isLoggedIn() ? $this->userManager->get($this->getUser()->id) : null;
        $form = $this->offerFormFactory->createFilterForm($params, $userLatitude, $userLongitude);
        $form->setMethod("GET");

        $form->onSuccess[] = function(Form $form, $values) {
            $noError = true;
            if (!empty($form->values["max" . OfferManager::COLUMN_PRICE])  && $form->values["max" . OfferManager::COLUMN_PRICE] < $form->values["min" . OfferManager::COLUMN_PRICE] && !empty($form->values["min" . OfferManager::COLUMN_PRICE])) {
                $form->addError($this->translator->translate("messages.homepage.error_min_max_price"));
                $noError = false;
            }
        };
        return $form;
    }

    public function renderUsermanagement() {
        $this->template->userId = UserManager::COLUMN_ID;
        $this->template->username = UserManager::COLUMN_NAME;
        if ($this->getUser()->id !== null) {
            $users = $this->userManager->getAll();
            $usersForTemplate = array();
            foreach ($users as $user) {
                if ($user[UserManager::COLUMN_ID] != $this->getUser()->id) {
                    array_push($usersForTemplate, $user);
                }
            }
            $this->template->users = $usersForTemplate;
        } else {
            $this->template->users = $this->userManager->getAll();
        }
    }
    
    protected function createComponentSubcategories(): SubcategoriesControl {
        $subcategoriesControl = new SubcategoriesControl($this->filterService);
        return $subcategoriesControl;
    }
    
    protected function createComponentBreadcrumb(): BreadcrumbControl {
        return new BreadcrumbControl($this->categoryManager ,
                                     !empty($this->getParameter('category')) ? $this->getParameter('category'): null,
                                     !empty($this->getParameter('id')) ? $this->offerManager : null,
                                     !empty($this->getParameter('id')) ? $this->getParameter('id'): null);
    }

    protected function beforeRender() {
        parent::beforeRender();
        $this->template->formPathInLine = __DIR__ . '/templates/formInLine.latte'; // Předá cestu ke globální šabloně formulářů do šablony.
    }

}
