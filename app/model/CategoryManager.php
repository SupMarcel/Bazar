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
        TABLE_NAME = 'kategorie',
        COLUMN_ID = 'id',
        COLUMN_TITLE = 'nazev',
        COLUMN_PARENT_CATEGORY='nadrazenaKategorie',
        COLUMN_IMAGE = 'obrazek';

    public function __construct(Nette\Database\Explorer $database)
    {
        parent::__construct($database);
    }

    public function getSubcategories($category){
        return $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_PARENT_CATEGORY, $category);
    }
}