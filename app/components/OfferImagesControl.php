<?php

namespace App\Components;

use App\Model\PhotoManager;
use Nette\Application\UI\Control;
use Nette\Database\Table\IRow;
use App\Model\OfferManager;

/**
 * Komponenta pro zobrazování a manipulaci s náhledy produktu.
 * @package App\EshopModule\Components
 */
class OfferImagesControl extends Control
{
    /** Cesta k souboru šablony pro tuto komponentu. */
    const TEMPLATE = __DIR__ . '/templates/offerImages.latte';

   
    private PhotoManager $photoManager;

    private OfferManager $offerManager;
    private $offerId = null;

    /**
    * Konstruktor komponenty s modelem pro práci s produkty.
    * @param ProductManager $productManager třída modelu pro práci s produkty předávaná standardně v rámci presenteru
    */
   public function __construct(PhotoManager $photoManager, OfferManager $offerManager)
   {
       $this->photoManager = $photoManager;
       $this->offerManager = $offerManager;
   }
   
   /**
    * Setter pro produkt.
    * @param bool|mixed|IRow $product product, pro který se má komponenta vykreslit
    */
   public function setPhotos($offer = null)
   {
       if ($offer){
            $this->offerId = $offer->{PhotoManager::COLUMN_ID};
       }else{
            $this->offerId = null;     
       }
   }
   
   /**
 * Vykresluje komponentu pro nastavený produkt.
 */
    public function render()
    {
        $this->template->setFile(self::TEMPLATE); // Nastaví šablonu komponenty.
        // Předává parametry do šablony.
        $this->template->offerId = $this->offerId;
        if(empty($this->offerId)){
            $this->template->imagesForUpload = isset($_SESSION["arrayPhotoName"]) ? $_SESSION["arrayPhotoName"]:[];
        }else{
            $this->template->imagesForUpload = $this->photoManager->getPhotoPathsByOffer($this->offerId);
        }
        $this->template->mainPhoto = isset($_SESSION["mainPhotoPath"]) ? $_SESSION["mainPhotoPath"] : '';
        $this->template->render(); // Vykreslí komponentu.
    }
    
     public function handleChangeMainPhotoInsert() {
        if ($this->getPresenter()->isAjax()){
           $fileName = $this->getPresenter()->getParameter("filename"); 
           $_SESSION["mainPhotoPath"] = $fileName;
           $this->redrawControl("photos");
        }
    }
    
    public function handleRemovePhotoInsertForm(){
        if($this->getPresenter()->isAjax()){
            $fileName = $this->getPresenter()->getParameter("filename");
            if(file_exists(__DIR__."/../../www/images/offers/".$fileName)){
                $this->photoManager->removePhotoByPath($fileName);
                //unlink(__DIR__."/../../www/images/offers/".$fileName);
            }
           
            $this->redrawControl("photos");
        }
    }
    
    public function handleChangemainphoto(){
        if($this->getPresenter()->isAjax()){
            $fileName = $this->getPresenter()->getParameter("filename");
            $offerID = null;
            if(!empty($this->getPresenter()->getParameter("offerID"))){
               $offerID = $this->getPresenter()->getParameter("offerID");
            }
            $_SESSION["mainPhotoPath"] = $fileName;
            if(!empty($offerID)){
                $this->photoManager->setMainPhotoByPath($fileName, $offerID);
            }
            $this->redrawControl("photos");
        }
    }

    public function handleUploadPhotos(){
        if($this->getPresenter()->isAjax()){
            $files = $this->getPresenter()->getHttpRequest()->getFiles();
            $offerID = isset($_POST["offerID"]) ? intval($_POST["offerID"]) : null; 
            $this->photoManager->insertPhotosfromAjax($files, $offerID);
            
            $this->redrawControl("photos");
        }
    }
    
    public function handleRemovePhoto(){
        if($this->getPresenter()->isAjax()){
            $fileName = $this->getPresenter()->getParameter("filename");
            $offerID = null;
            if(!empty($this->getPresenter()->getParameter("offerID"))){
                $offerID = $this->getPresenter()->getParameter("offerID");
            }
            
            $this->photoManager->removePhotoByPath($fileName, $offerID);
            $this->redrawControl("photos");
        }
    }
}