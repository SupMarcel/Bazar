<?php

namespace App\Control;

use Nette;
use Nette\Application\UI\Control;
use App\Model\CategoryManager;
use App\Model\OfferManager;

class BreadcrumbControl extends Control {

    private $categoryManager;
    private $category;
    private $offerManager;
    private $offerId;

    public function __construct(CategoryManager $categoryManager,
                                $category = null,
                                OfferManager $offerManager = null,
                                $offerId = null) {
        $this->categoryManager = $categoryManager;
        $this->category = $category;
        $this->offerManager = $offerManager;
        $this->offerId = $offerId;
    }
    
    public function render() {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/breadcrumbControl.latte');
        if(empty($this->offerId)){
            $template->noCategory = empty($this->category);
        }
        if (!empty($this->category)){
            $template->parentCategories = $this->categoryManager->breadcrumb($this->category);
        }
        if(!empty($this->offerId)&& !empty($this->offerManager)){
           $offer = $this->offerManager->get($this->offerId); 
           $template->offerTitle = $offer->{OfferManager::COLUMN_TITLE};
           $category = $offer->{OfferManager::COLUMN_CATEGORY};
           $template->parentCategories = $this->categoryManager->breadcrumb($category);
           $template->noCategory = false;
        }
        $template->render();
    }

    public function handleChangeCategory($categoryId) {
        $this->presenter->redirect('Homepage:default', ['category' => $categoryId]);
    }
}
