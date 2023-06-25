<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 18:42
 */

namespace App\Model;


use Nette;
use Tracy\ILogger;
use Nette\Database\Explorer;



class AddressManager extends BaseManager
{
    const
        TABLE_NAME = 'address',
        COLUMN_ID = 'id',
        COLUMN_STREET = 'street_house_number',
        COLUMN_REGION = 'region',
        COLUMN_CITY = 'city',
        COLUMN_STATE = 'state',
        COLUMN_ZIP_CODE = 'zip_code',
        COLUMN_LATITUDE =  'latitude',
        COLUMN_LONGITUDE = 'longitude',
        COLUMN_USER = 'user_id';
    
    
    public function __construct(Explorer $database, ILogger $logger)
    {
        parent::__construct($database,$logger);
    }
    
    public function addAddress($completAddress, $userId) {
        $row = $this->database->table(self::TABLE_NAME)->insert([
            self::COLUMN_STREET => $completAddress[self::COLUMN_STREET],
            self::COLUMN_REGION => $completAddress[self::COLUMN_REGION],
            self::COLUMN_CITY => $completAddress[self::COLUMN_CITY],
            self::COLUMN_STATE => $completAddress[self::COLUMN_STATE],
            self::COLUMN_ZIP_CODE => $completAddress[self::COLUMN_ZIP_CODE],
            self::COLUMN_LATITUDE => $completAddress[self::COLUMN_LATITUDE],
            self::COLUMN_LONGITUDE => $completAddress[self::COLUMN_LONGITUDE],
            self::COLUMN_USER => $userId
        ]);
    
        return $row->id;
    }
    
    public function getAddressIdByStreet($street , $userId){
        $row = $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_STREET, $street)
            ->where(self::COLUMN_USER, $userId );  
        return $row->id;
    }
    
    public function getAddresses($userId) {
        return $this->database->table(self::TABLE_NAME)
              ->where(self::COLUMN_USER, $userId)->fetchAll();   
    }
    
    public function deleteAddressByStreet($street , $userId) {
        $row = $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_STREET, $street)
            ->where(self::COLUMN_USER, $userId );  
         $row->delete();
    }
    
    public function countAddresses($userId=null) {
        if($userId==null){
            return 0;
        }
        return $this->database->table(self::TABLE_NAME)
                ->where(self::COLUMN_USER, $userId)->count('*');
    }
    
    function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'kilometers') {
    $theta = $longitude1 - $longitude2; 
    $distance = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta))); 
    $distance = acos($distance); 
    $distance = rad2deg($distance); 
    $distance = $distance * 60 * 1.1515; 
    switch($unit) { 
      case 'miles': 
        break; 
      case 'kilometers' : 
        $distance = $distance * 1.609344; 
    } 
    return (round($distance,2)); 
    }
}
