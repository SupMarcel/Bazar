<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 18:41
 */

namespace App\Model;

use Nette;
use Nette\Database\Explorer;

class BaseManager
{
    use Nette\SmartObject;

    const
        TABLE_NAME = 'table',
        COLUMN_ID = 'id',
        CITY = 1,
		PAGE_SIZE = 20;


    /** @var Nette\Database\Explorer */
    protected $database;


    public function __construct(Explorer $database)
    {
        $this->database = $database;
    }

    public function get($id){
        return $this->database->table(static::TABLE_NAME)->get($id);
    }

    public function getAll(){
        return $this->database->table(static::TABLE_NAME);
    }

    public function remove($id){
        $this->database->table(static::TABLE_NAME)->where(static::COLUMN_ID, $id)->delete();
    }

    public function getNextId() {
        $count = $this->database->table(static::TABLE_NAME)->count(static::COLUMN_ID);
        $maxID = $this->database->table(static::TABLE_NAME)->max(static::COLUMN_ID);
        $nextId = $count == 0 ? 1 : $maxID + 1;
        return $nextId;
    }   
}