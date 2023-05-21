<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 18:41
 */

namespace App\Model;


use Nette;

class CategoryManager extends BaseManager
{
    const
        TABLE_NAME = 'category',
        COLUMN_ID = 'id',
        COLUMN_TITLE = 'title',
        COLUMN_PARENT_CATEGORY='parent_category',
        COLUMN_IMAGE = 'image';
    
    private UserManager $userManager;
    private AddressManager $addressManager;

    public function __construct(Nette\Database\Explorer $database, UserManager $userManager, AddressManager $addressManager)
    {
        parent::__construct($database);
        $this->userManager = $userManager;
        $this->addressManager = $addressManager;
    }

    public function getSubcategories($categoryId = null){
        return $this->database->table(self::TABLE_NAME)
                    ->where(self::COLUMN_PARENT_CATEGORY, $categoryId)
                    ->fetchAll();
    }
    
    public function getSubcategoriesWithCategory($categoryId) {
        return $this->database->table(self::TABLE_NAME)
                    ->whereOr([self::COLUMN_PARENT_CATEGORY => $categoryId,
                               self::COLUMN_ID => $categoryId ])
                    ->fetchAll();
    }
    
    public function getSubcategoryIds($categoryId){
        return $this->database->table(self::TABLE_NAME)
                    ->where(self::COLUMN_PARENT_CATEGORY, $categoryId)
                    ->fetchPairs(null, self::COLUMN_ID);
    }
    
    
    
     private function formatTree($categories, $parentId)
   {
       $tree = array(); // Vytvoříme prázdný strom
       // Pokusíme se najít položky, které patří do rodičovské kategorie ($parentId)
       foreach ($categories as $category) {
           if ($category->{self::COLUMN_PARENT_CATEGORY} == $parentId) {
               // Položku přidáme do nového stromu a rekurzivně přidáme strom podpoložek
               $tree[$category->{self::COLUMN_ID}] = [
                   'category' => $category,
                   'subcategories' => $this->formatTree($categories, $category->{self::COLUMN_ID})
               ];
           }
       }
       return $tree; // Vrátíme hotový strom
   }
   
   /**
    * Vrátí kategorie produktů v podobě stromu.
    * @param bool $showAll zda chceme zobrazovat i skryté kategorie
    * @return array kategorie produktů v podobě stromu
    */
   public function getCategories()
   {
       $categories = $this->database->table(self::TABLE_NAME); // Získá seznam kategorií z databáze.
       
       $categories->order(self::COLUMN_TITLE); // Seřadí výsledek.
       return $this->formatTree($categories->fetchAll(), null); // Vratí výsledný strom.
   }
    public function breadcrumb($categoryID) {
        $parentCategories = array();
        if(!empty($categoryID)){
            $category = $this->get($categoryID);
            while(true && !empty($category)){
                $category = $category->toArray();
                if($category[self::COLUMN_ID] === intval($categoryID)){
                   $category['last'] = intval($categoryID);
                }else{
                   $category['last'] = ""; 
                }
                array_push($parentCategories, $category);
                $category = $this->get($category[self::COLUMN_PARENT_CATEGORY]);
            }
        return array_reverse($parentCategories);
        } 
    }
}  
