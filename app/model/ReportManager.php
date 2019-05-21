<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 18:42
 */

namespace App\Model;


use Nette;

class ReportManager extends BaseManager
{
    const
        TABLE_NAME = 'nahlaseni',
        COLUMN_ID = 'id',
        COLUMN_USER = 'uzivatel',
        COLUMN_OFFER = 'nabidka',
        COLUMN_REASON = 'duvod';

    public function __construct(Nette\Database\Context $database)
    {
        parent::__construct($database);
    }

    public function addReport($properties){
        $count = $this->database->table(self::TABLE_NAME)->count(self::COLUMN_ID);
        $maxID = $this->database->table(self::TABLE_NAME)->max(self::COLUMN_ID);
        $max = $count == 0 ? 1 : $maxID + 1;
        $this->database->table(self::TABLE_NAME)->insert([
            self::COLUMN_ID => $max,
            self::COLUMN_USER => $properties[self::COLUMN_USER],
            self::COLUMN_OFFER => $properties[self::COLUMN_OFFER],
            self::COLUMN_REASON => $properties[self::COLUMN_REASON]
        ]);
    }

    public function getReportsByOffer($offer){
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_OFFER, $offer);
    }
}