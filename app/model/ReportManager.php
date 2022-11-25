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
        COLUMN_USER = 'uzivatel',
        COLUMN_OFFER = 'nabidka',
        COLUMN_REASON = 'duvod';

   

    public function addReport($properties){
        $this->database->table(self::TABLE_NAME)->insert([
            self::COLUMN_USER => $properties[self::COLUMN_USER],
            self::COLUMN_OFFER => $properties[self::COLUMN_OFFER],
            self::COLUMN_REASON => $properties[self::COLUMN_REASON]
        ]);
    }

    public function getReportsByOffer($offer){
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_OFFER, $offer);
    }
}