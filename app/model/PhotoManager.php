<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 18:42
 */

namespace App\Model;


use Nette;

class PhotoManager extends BaseManager
{
    const
        TABLE_NAME = 'fotografie',
        COLUMN_ID = 'id',
        COLUMN_PATH = 'cesta',
        COLUMN_OFFER = 'nabidka';


    


    public function getPhotosByOffer($offer){
        return $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_OFFER, $offer);
    }

    public function getCountPhotosByOffer($offer){
        return $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_OFFER, $offer)->count(self::COLUMN_ID);
    }


    public function getIDOfMainPhoto($offer){
        return $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_OFFER, $offer)->min(self::COLUMN_ID);
    }


    public function getNotMainPhotosByOffer($offerID){
        $minPhotoID = $this->database->table(self::TABLE_NAME)->
        where(self::COLUMN_OFFER, $offerID)->min(self::COLUMN_ID);
        $photos = $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_OFFER, $offerID);
        $notMainPhotos = array();
        foreach($photos as $photo){
            $id = $photo[self::COLUMN_ID];
            if($id != $minPhotoID){
                array_push($notMainPhotos, $photo);
            }
        }
        return $notMainPhotos;
    }

    public function addPhoto($properties){
       
        $row = $this->database->table(self::TABLE_NAME)->insert([
            self::COLUMN_ID => $this->getNextId(),
            self::COLUMN_PATH => $properties[self::COLUMN_PATH],
            self::COLUMN_OFFER => $properties[self::COLUMN_OFFER]   
        ]);
        return $row->id;
    }

    public function editPhoto($id,$properties) {
        $row = $this->get($id);
        if ($row){
            $row->update([self::COLUMN_OFFER => $properties[self::COLUMN_OFFER]]);
        }
    }
    
    public function changeMainPhoto($offerID, $newPhoto){
        $minPhotoID = $this->database->table(self::TABLE_NAME)->
        where(self::COLUMN_OFFER, $offerID)->min(self::COLUMN_ID);
        $mainPhoto = $this->database->table(self::TABLE_NAME)->get($minPhotoID);
        $oldPhotoTitle = $mainPhoto[self::COLUMN_PATH];
        unlink(__DIR__ . "/../../www/images/offers/".$oldPhotoTitle);
        $mainPhoto->update([self::COLUMN_PATH => $newPhoto]);

    }

    public function removePhoto($photoID){
        $photo = $this->database->table(self::TABLE_NAME)->get($photoID);
        $path = $photo[self::COLUMN_PATH];
        unlink(__DIR__ . "/../../www/images/offers/".$path);
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $photoID)->delete();
    }

    public function changePhotoByTitle($photoID, $newPhoto){
        $photos = $this->database->table(self::TABLE_NAME)->get($photoID);
        foreach($photos as $photo){
            $path = $photo[self::COLUMN_PATH];
            unlink(__DIR__ . "/../../www/images/offers/".$path);
            $photo->update([self::COLUMN_PATH => $newPhoto]);
        }
    }
}