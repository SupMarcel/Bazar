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
use Tracy\ILogger;


class BaseManager
{
    use Nette\SmartObject;

    const
        TABLE_NAME = 'table',
        COLUMN_ID = 'id',
        PAGE_SIZE = 20;


    /** @var Nette\Database\Explorer */
    protected $database;
    
    private $logger;


    public function __construct(Explorer $database, ILogger $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }
    
    
    protected function logError($message)
    {
        $this->logger->log($message, ILogger::EXCEPTION);
    }
    
    public function get($id){
        return $this->database->table(static::TABLE_NAME)->get($id);
    }

    public function getAll(int $limit = null, int $offset = null){
        return $this->database->table(static::TABLE_NAME)->limit($limit, $offset);
    }                         

    public function remove($id){
        $this->database->table(static::TABLE_NAME)->where(static::COLUMN_ID, $id)->delete();
    }

    /*public function getNextId() {
        $count = $this->database->table(static::TABLE_NAME)->count(static::COLUMN_ID);
        $maxID = $this->database->table(static::TABLE_NAME)->max(static::COLUMN_ID);
        $nextId = $count == 0 ? 1 : $maxID + 1;
        return $nextId;
    } */
    
    public function getNextId() {
        $maxID = $this->database->table(static::TABLE_NAME)->max(static::COLUMN_ID);
        return $maxID ? $maxID + 1 : 1;
    }
}