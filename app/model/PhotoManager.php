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
use Nette\Utils\FileSystem;
use Tracy\ILogger;
use Nette\Database\Explorer;

class PhotoManager extends BaseManager
{
    const
        TABLE_NAME = 'photo',
        COLUMN_ID = 'id',
        COLUMN_PATH = 'path',
        TABLE_PHOTO_OFFER = 'photo_offer',
        COLUMN_PHOTO_ID = 'photo_id',    
        COLUMN_OFFER = 'offer',
            
        DEFAULT_PHOTO_ID = 500,
        DEFAULT_PHOTO_PATH = '1.jpg' ;   
    
    
    
    private $arrayPhotoName = [];
    private $arrayOldPhotoName = [];
    
    public string $FOLDER_FOR_OFFER_PICTURES;
    
    public function __construct(string $folderForOfferPicture,  Nette\Database\Explorer $database, ILogger $logger)
    {
        parent::__construct($database,$logger);
        $this->FOLDER_FOR_OFFER_PICTURES = $folderForOfferPicture;
        FileSystem::createDir($this->FOLDER_FOR_OFFER_PICTURES);
    }    

    public function getPhotosByOffer($offer){
        return $this->database->table(self::TABLE_PHOTO_OFFER)
            ->where(self::COLUMN_OFFER, $offer);
    }
    
    public function getPhotoPathsByOffer($offerID){
        $_SESSION["arrayPhotoName"] = [];
        $offerPhotos = $this->database->table(self::TABLE_PHOTO_OFFER)
            ->where(self::COLUMN_OFFER, $offerID)
            ->fetchAll();
        $mainPhotoId = $this->getIdOfMainPhoto($offerID);
        if (($mainPhotoId != self::DEFAULT_PHOTO_ID )&&(!empty($mainPhotoId))){
            $_SESSION["mainPhotoPath"] = $this->PhotoPath($mainPhotoId);
        }
        foreach ($offerPhotos as $offerPhoto){
            $_SESSION["arrayPhotoName"][] = $offerPhoto->{self::TABLE_NAME}->{self::COLUMN_PATH};
        }
        return $_SESSION["arrayPhotoName"];     
    }
    
    public function deletePhotosByOffer($offer){
        return $this->database->table(self::TABLE_PHOTO_OFFER)
               ->where(self::COLUMN_OFFER, $offer)
               ->delete() ;
    }
    
    public function removePhotos($id){
        $photos = $this->getPhotosByOffer($id);
        foreach($photos as $photo){
            $filename = $photo[self::COLUMN_PATH];
            unlink(__DIR__ . "/../../www/images/offers/".$filename);
        }
         $this->deletePhotosByOffer($id);
    }

    public function getCountPhotosByOffer($offer){
        return $this->database->table(self::TABLE_PHOTO_OFFER)
            ->where(self::COLUMN_OFFER, $offer)->count(self::COLUMN_PHOTO_ID);
    }

   
    public function addPhoto($properties){
       
       $this->database->table(self::TABLE_NAME)->insert([
            self::COLUMN_ID => $properties[self::COLUMN_ID],
            self::COLUMN_PATH => $properties[self::COLUMN_PATH],
              ]);
       $this->database->table(self::TABLE_PHOTO_OFFER)->insert([
            self::COLUMN_PHOTO_ID => $properties[self::COLUMN_PHOTO_ID],
            self::COLUMN_OFFER => $properties[self::COLUMN_OFFER],
              ]); 
    }

    public function editPhoto($id,$properties) {
       $this->database->table(self::TABLE_PHOTO_OFFER)
                       ->where(self::COLUMN_PHOTO_ID, $id)
                       ->where(self::COLUMN_OFFER, null)
                       ->update([self::COLUMN_OFFER => $properties[self::COLUMN_OFFER]]);
                      
    }
  
    
    public function removePhotoByPath2($path) {
        $mainPhoto = !empty($_SESSION["mainPhoto"]) ? $_SESSION["mainPhoto"] : $this->mainPhotoID($path);
        if (($key = array_search($photoId, $_SESSION["arrayPhoto"])) !== false) {
            unset($_SESSION["arrayPhoto"][$key]);
            if (($key = array_search($path, $_SESSION["arrayPhotoName"])) !== false) {
                unset($_SESSION["arrayPhotoName"][$key]);
                if ($path == $_SESSION["mainPhotoPath"]) {
                    if (!empty($_SESSION["arrayPhoto"])){
                        $_SESSION["mainPhoto"] = min($_SESSION["arrayPhoto"]);
                        $_SESSION["mainPhotoPath"]= $this->PhotoPath($_SESSION["mainPhoto"]);
                    }
                    else {

                    }
                };   
            }
            unlink(__DIR__ . "/../../www/images/offers/".$path);
            $this->database->table(self::TABLE_PHOTO_OFFER)->where(self::COLUMN_PHOTO_ID, $photoId)->delete();
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_PATH, $path)->delete();

        }
    }
    
    public function removePhotoByPath($path, $offerID = null) {
        if(empty($offerID)){
        $mainPhoto = !empty($_SESSION["mainPhotoPath"]) ? $_SESSION["mainPhotoPath"] : "";
        }else{
            $IdMainPhoto = $this->getIdOfMainPhoto($offerID);
            $mainPhoto = !empty($_SESSION["mainPhotoPath"]) ? $_SESSION["mainPhotoPath"] : $this->PhotoPath($IdMainPhoto);
        }
        if ($path == $mainPhoto ){
            
            foreach ($_SESSION["arrayPhotoName"] as $photo){
                if ($photo == $mainPhoto){
                    continue;
                } else{
                    $_SESSION["mainPhotoPath"] = $photo;
                    break;
                }
            }
            
            }
            if(!empty($offerID)){
                if (!empty($_SESSION["mainPhotoPath"])){
                $newIDmainPhoto = $this->PhotoID($_SESSION["mainPhotoPath"]);
                }else{
                    $newIDmainPhoto = self::DEFAULT_PHOTO_ID;
                }   
                $this->setMainPhoto($newIDmainPhoto, $offerID);
            
        }
        if (($key = array_search($path, $_SESSION["arrayPhotoName"])) !== false) {
            unset($_SESSION["arrayPhotoName"][$key]);
            };   
            unlink(__DIR__ . "/../../www/images/offers/".$path);
            $photoId = $this->PhotoID($path);
            if(isset($photoId)){
                $this->database->table(self::TABLE_PHOTO_OFFER)->where(self::COLUMN_PHOTO_ID, $photoId)->delete();
                $this->database->table(self::TABLE_NAME)->where(self::COLUMN_PATH, $path)->delete();
            }
   }

    public function insertPhotos($photos, $offerID = null) {
        if(!empty($photos)){                     
                                    $arrayPhoto = [];
                                                                        
                                    foreach ($photos as $photo){
                                     $idPhoto = $this->getNextId();
                                    
                                     $name = $photo->getSanitizedName();
                                     $photoValues = [
                                        self::COLUMN_ID => $idPhoto,
                                        self::COLUMN_PATH => $idPhoto.$name,
                                        self::COLUMN_OFFER => $offerID,
                                        self::COLUMN_PHOTO_ID => $idPhoto 
                                     ];
                                     $this->addPhoto($photoValues);
                                     $photo->move(__DIR__."/../../www/images/offers/".$idPhoto.$name);
                                     $arrayPhoto[] = $idPhoto;
                                     
                                    }
                                     $_SESSION["arrayPhoto"]= $arrayPhoto;
                                     $_SESSION["mainPhoto"] = $arrayPhoto[0];
                                     
                return $arrayPhoto[0];                     
        }
       
        
    }
   
    
     public function editOfferPhotos($IdOffer ) {
       if (empty($_SESSION["arrayPhotoName"]))  {
          $photoValues = [
            self::COLUMN_ID => self::DEFAULT_PHOTO_ID,
            self::COLUMN_PATH => self::DEFAULT_PHOTO_PATH,
            self::COLUMN_OFFER => $IdOffer ,
            self::COLUMN_PHOTO_ID => self::DEFAULT_PHOTO_ID  
            ];
            $this->addPhoto($photoValues);  
       }else{
        foreach ($_SESSION["arrayPhotoName"] as $photo){
            $photoValues = [
                self::COLUMN_OFFER => $IdOffer 
            ];
            $PhotoId = $this->PhotoID($photo);
            $this->editPhoto($PhotoId, $photoValues);
       }}
        $_SESSION["arrayPhotoName"]= [];
    }
    
    public function insertPhotosfromAjax($photos,$offerID = null) {
        if(!empty($photos)){                     
                                    if ($offerID){
                                        $this->getOldPhotos($offerID);
                                    }else{
                                        $this->arrayPhotoName =!empty($_SESSION["arrayPhotoName"]) ? $_SESSION["arrayPhotoName"]:[];
                                    }
                                    foreach ($photos as $photo){
                                        if($offerID){    
                                           if (!empty(array_search($photo->getSanitizedName(), $this->arrayOldPhotoName))){
                                               continue;
                                               }
                                        }   
                                     $idPhoto = $this->getNextId();
                                     $name = $photo->getSanitizedName();
                                     $photoValues = [
                                        self::COLUMN_ID => $idPhoto,
                                        self::COLUMN_PATH => $idPhoto.$name,
                                        self::COLUMN_OFFER => $offerID ,
                                        self::COLUMN_PHOTO_ID => $idPhoto 
                                     ];
                                     $this->addPhoto($photoValues);
                                     move_uploaded_file($photo->getTemporaryFile(), __DIR__."/../../www/images/offers/".$idPhoto.$name);
                                     
                                     $this->arrayPhotoName[] = $idPhoto.$name;
                                    
                                    }
                                    
                                     $_SESSION["arrayPhotoName"] = $this->arrayPhotoName;
                                     
                                    
                                     if (empty($_SESSION["mainPhotoPath"])){
                                        $_SESSION["mainPhotoPath"] = $this->arrayPhotoName[0];
                                     }
                                     $mainPhotoID = $this->PhotoID($_SESSION["mainPhotoPath"]);
                                     return $mainPhotoID;
        }
       
        
    }
    
    private function PhotoPath($PhotoID) {
        $row = $this->database->table(self::TABLE_NAME)->get($PhotoID);
        return $row->cesta;
    }
    public function PhotoID($path) {
       $row = $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_PATH, $path)->fetch(); 
       return $row->id;
    }
    
    
    private function getOldPhotos($offerId) {
        $this->arrayPhotoName =!empty($_SESSION["arrayPhotoName"]) ? $_SESSION["arrayPhotoName"]:[];
        $oldPhotos = $this->getPhotosByOffer($offerId);
        foreach ($oldPhotos as $oldPhoto){
            if($oldPhoto->{self::COLUMN_PHOTO_ID} == self::DEFAULT_PHOTO_ID){
                $this->editIdOfMainPhoto($offerId);
                $this->deletePhotosByOffer($offerId);
                continue;
            }
            $this->arrayPhotoName[] = $oldPhoto->{self::TABLE_NAME}->{self::COLUMN_PATH}; 
            $this->arrayOldPhotoName[] = $oldPhoto->{self::TABLE_NAME}->{self::COLUMN_PATH};
        }
        $_SESSION["arrayPhotoName"] = $this->arrayPhotoName;
        $_SESSION["arrayOldPhotoName"] = $this->arrayOldPhotoName;        
        $mainPhotoID = $this->getIdOfMainPhoto($offerId);
        $mainPhoto = $this->get($mainPhotoID);
        $_SESSION["mainPhotoPath"] = $mainPhoto->{self::COLUMN_PATH};
        
    }
    
    public function getIdOfMainPhoto($offerId){
        $row = $this->database->table(OfferManager::TABLE_NAME)
                              ->where(OfferManager::COLUMN_ID, $offerId)
                              ->fetch();
        return $row->hlavniFotografie;
    }
    
    private function editIdOfMainPhoto($offerId, $IdMainPhoto=null) {
        $this->database->table(OfferManager::TABLE_NAME)
                       ->where(OfferManager::COLUMN_ID, $offerId)
                       ->update([OfferManager::COLUMN_MAIN_PHOTO =>$IdMainPhoto]);
    }
    
    public function removePhoto($id, $offerID) {
        $this->database->table(self::TABLE_PHOTO_OFFER)
                       ->where(self::COLUMN_PHOTO_ID, $id)
                       ->where(self::COLUMN_OFFER, $offerID)
                       ->delete();
    }
    
    public function setMainPhoto($photoID, $offerID){
        
        $offer = $this->database->table(OfferManager::TABLE_NAME)->get($offerID);
        $offer->update([
            OfferManager::COLUMN_MAIN_PHOTO => $photoID
            ]
        );
    }
    
      public function setMainPhotoByPath($path, $offerID){
        $photoID = $this->PhotoID($path);  
        $offer = $this->database->table(OfferManager::TABLE_NAME)->get($offerID);
        $offer->update([
            OfferManager::COLUMN_MAIN_PHOTO => $photoID
            ]
        );
    }
    
    public function addIconPhoto($iconPhoto, $genders) {
        if(!empty($iconPhoto)){
            $iconPhotoId = $this->getNextId();
            $name = $iconPhoto->getSanitizedName();
             $this->database->table(self::TABLE_NAME)->insert([
            self::COLUMN_ID => $iconPhotoId,
            self::COLUMN_PATH => $iconPhotoId.$name,
              ]);
              // Vytvořte adresář, pokud neexistuje
            $directoryPath = __DIR__ . "/../../www/images/offers/";
            if (!is_dir($directoryPath)) {
                mkdir($directoryPath, 0777, true);  // Parametr true umožní vytvoření více vnořených adresářů
            } 
            $path = $directoryPath.$iconPhotoId.$name;
            $iconPhoto->move($path); 
            return $iconPhotoId; 
        }else{
            if ($genders == 0){
                return 94;
            }else{
                 return 95; 
            }
        }
    }
}