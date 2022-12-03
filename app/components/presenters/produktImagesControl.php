<?php

namespace App\EshopModule\components;

use App\EshopModule\Model\ProductManager;
use Nette\Application\UI\Control;
use Nette\Database\Table\IRow;

/**
 * Komponenta pro zobrazování a manipulaci s náhledy produktu.
 * @package App\EshopModule\Components
 */
class ProductImagesControl extends Control
{
    /** Cesta k souboru šablony pro tuto komponentu. */
    const TEMPLATE = __DIR__ . '/../templates/components/productImages.latte';

    /** @var ProductManager Instance třídy modelu pro práci s produkty. */
    private ProductManager $productManager;

    /** @var null|int ID produktu, pro který se má komponenta vykreslit, nebo null pro nový produkt. */
    private $productId = null;

    /** @var int Počet obrázků u daného produktu nebo nula pro nový produkt. */
    private int $imagesCount = 0;
    
    /**
    * Konstruktor komponenty s modelem pro práci s produkty.
    * @param ProductManager $productManager třída modelu pro práci s produkty předávaná standardně v rámci presenteru
    */
   public function __construct(ProductManager $productManager)
   {
       $this->productManager = $productManager;
   }
   
   /**
    * Setter pro produkt.
    * @param bool|mixed|IRow $product product, pro který se má komponenta vykreslit
    */
   public function setProduct($product)
   {
       $this->productId = $product->product_id;
       $this->imagesCount = $product->images_count;
   }
   
   /**
 * Vykresluje komponentu pro nastavený produkt.
 */
    public function render()
    {
        $this->template->setFile(self::TEMPLATE); // Nastaví šablonu komponenty.
        // Předává parametry do šablony.
        $this->template->productId = $this->productId;
        $this->template->imagesCount = $this->imagesCount;
        $this->template->render(); // Vykreslí komponentu.
    }
    
    /**
    * Signál pro odstranění náhledu produktu.
    * @param int $id    ID produktu
    * @param int $index index náhledu (0 je první)
    */
   public function handleDeleteImage($id, $index)
   {
       $this->productManager->removeProductImage($id, $index);
       $presenter = $this->getPresenter();
       if ($presenter->isAjax()) {
           $this->imagesCount--;
           $this->redrawControl();
       } else $presenter->redirect('this');
   }
}