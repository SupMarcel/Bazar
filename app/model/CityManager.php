<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 21:26
 */

namespace App\Model;


use Nette;
use Tracy\ILogger;
use Nette\Database\Explorer;

class CityManager extends BaseManager
{
    const
        TABLE_NAME = 'obec',
        COLUMN_ID = 'id',
        COLUMN_TITLE = 'nazev',
        COLUMN_REGION = 'okres';
    
    public function __construct(Explorer $database, ILogger $logger)
    {
        parent::__construct($database,$logger);
    }
   
}

