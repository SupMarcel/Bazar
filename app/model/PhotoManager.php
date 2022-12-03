<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.8.2018
 * Time: 18:42
 */

namespace App\Model;


use Nette;
use Nette\Http\FileUpload;



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
    
    public function removePhotoByPath($path) {
        $photoId = $this->mainPhotoID($path);
        if (($key = array_search($photoId, $_SESSION["arrayPhoto"])) !== false) {
            unset($_SESSION["arrayPhoto"][$key]);
        if (($key = array_search($path, $_SESSION["arrayPhotoName"])) !== false) {
            unset($_SESSION["arrayPhotoName"][$key]);
        if ($path == $_SESSION["mainPhoto"] ) {
            if (!empty($_SESSION["arrayPhoto"])){
                $_SESSION["mainPhoto"]= $this->mainPhotoPath(min($_SESSION["arrayPhoto"]));
            }
            else {
                
            }
        };   
        }
        unlink(__DIR__ . "/../../www/images/offers/".$path);
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_PATH, $path)->delete();
        
    }}

    public function changePhotoByTitle($photoID, $newPhoto){
        $photos = $this->database->table(self::TABLE_NAME)->get($photoID);
        foreach($photos as $photo){
            $path = $photo[self::COLUMN_PATH];
            unlink(__DIR__ . "/../../www/images/offers/".$path);
            $photo->update([self::COLUMN_PATH => $newPhoto]);
        }
    }
    
    public function insertPhotos($photos) {
        if($photos == []){
	// $form->addError("Prosím nahrajte alespoň jednu fotografii.");
	}else {                     
                                    $arrayPhoto = [];
                                    
                                    
                                    foreach ($photos as $photo){
                                     $idPhoto = $this->getNextId();
                                    
                                     $name = $photo->getSanitizedName();
                                     $photoValues = [
                                        self::COLUMN_ID => $idPhoto,
                                        self::COLUMN_PATH => $idPhoto.$name,
                                        self::COLUMN_OFFER => null 
                                     ];
                                     $this->addPhoto($photoValues);
                                     $photo->move(__DIR__."/../../www/images/offers/".$idPhoto.$name);
                                     $arrayPhoto[] = $idPhoto;
                                     
                                    }
                                     $_SESSION["arrayPhoto"]= $arrayPhoto;
                                     
                                     
        }
       
        return $arrayPhoto[0];
    }
    
     public function editOfferPhotos($IdOffer ) {
        foreach ($_SESSION["arrayPhoto"] as $photo){
            $photoValues = [
                self::COLUMN_OFFER => $IdOffer 
            ];
            $this->editPhoto($photo, $photoValues);
        }
        $_SESSION["arrayPhoto"]= [];
    }
    
    public function insertPhotosfromAjax($photos) {
        if($photos == []){
	// $form->addError("Prosím nahrajte alespoň jednu fotografii.");
	}else {                     
                                    $arrayPhoto = [];
                                    $arrayPhotoName = [];
                                    
                                    foreach ($photos as $photo){
                                     $idPhoto = $this->getNextId();
                                 
                                     $name = $photo['name'];
                                     $photoValues = [
                                        self::COLUMN_ID => $idPhoto,
                                        self::COLUMN_PATH => $idPhoto.$name,
                                        self::COLUMN_OFFER => null 
                                     ];
                                     $idPhoto = $this->addPhoto($photoValues);
                                     move_uploaded_file($photo['tmp_name'], __DIR__."/../../www/images/offers/".$idPhoto.$name);
                                     $arrayPhoto[] = $idPhoto;
                                     $arrayPhotoName[] = $idPhoto.$name;
                                    }
                                     $_SESSION["arrayPhoto"]= $arrayPhoto;
                                     $_SESSION["arrayPhotoName"] = $arrayPhotoName;
                                     $mainPhotoPath = $this->mainPhotoPath($arrayPhoto[0]);
                                     $_SESSION["mainPhoto"]= $mainPhotoPath;
        }
       
        
    }
    
    private function mainPhotoPath($mainPhotoID) {
        $row = $this->database->table(self::TABLE_NAME)->get($mainPhotoID);
        return $row->cesta;
    }
    public function mainPhotoID($path) {
       $row = $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_PATH, $path)->fetch(); 
       return $row->id;
    }
    
}