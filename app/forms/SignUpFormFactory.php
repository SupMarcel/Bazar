<?php

namespace App\Forms;

use App\Model\UserManager;
use Nette;
use Nette\Application\UI\Form;
use App\Model\RegistrationManager;
use App\Model\AddressManager;
use Nette\Database;
use App\Model\DuplicateNameException;



class SignUpFormFactory extends FormFactory
{
	use Nette\SmartObject;

	const PASSWORD_MIN_LENGTH = 8;

	/** @var Model\UserManager */
	private $userManager;
	/** @var  Model\RegistrationManager */
	private $registrationManager;
	private $user;
        private $addressManager;
     
	public function __construct(UserManager $userManager,
                                RegistrationManager $registrationManager,
                                AddressManager $addressManager)
	{
		$this->userManager = $userManager;
		$this->registrationManager = $registrationManager;
		$this->user = null;
                $this->addressManager = $addressManager;
   	}


	public function createEditForm($user){
            
	    
	    $this->user = $user;

        $identity = $this->userManager->get($this->user);
        $userArray = $identity->toArray();
        $activeAddressId = $identity->{UserManager::COLUMN_ACTIVE_ADDRESS_ID};
        $activeAddress = $this->addressManager->get($activeAddressId);
        $identityArray = $activeAddress->toArray(); // IRow je read-only, pro manipulaci s ním ho musíme převést na pole.
        $identityArray = array_merge($identityArray,$userArray);
        $userId = $identity->{UserManager::COLUMN_ID};
        /*$activeAddress = $this->addressManager->get($identityArray[UserManager::COLUMN_ACTIVE_ADDRESS_ID]);
        $identityArray[AddressManager::COLUMN_ID] = $activeAddress->{AddressManager::COLUMN_ID};
        $identityArray[AddressManager::COLUMN_STREET] = $activeAddress->{AddressManager::COLUMN_STREET};
        $identityArray[AddressManager::COLUMN_CITY] = $activeAddress->{AddressManager::COLUMN_CITY};
        $identityArray[AddressManager::COLUMN_REGION]= $activeAddress->{AddressManager::COLUMN_REGION};
        $identityArray[AddressManager::COLUMN_ZIP_CODE]= $activeAddress->{AddressManager::COLUMN_ZIP_CODE};
        $identityArray[AddressManager::COLUMN_STATE] = $activeAddress->{AddressManager::COLUMN_STATE};*/       
        
        $countAddresses = $this->addressManager->countAddresses($userId);
        $form = $this->createForm(false, $userId);        
         if ($countAddresses>1){
           $addresses = $this->addressManager->getAddresses($userId); 
           $i=2;
           foreach ($addresses as $address){
               $addressArray = [];
               if($address->{AddressManager::COLUMN_ID} == $identity->{UserManager::COLUMN_ACTIVE_ADDRESS_ID}){
                    continue;
                } else{
                    $addressArray[AddressManager::COLUMN_ID.$i] = $address->{AddressManager::COLUMN_ID};
                    $addressArray[AddressManager::COLUMN_STREET.$i] = $address->{AddressManager::COLUMN_STREET};
                    $addressArray[AddressManager::COLUMN_CITY.$i] = $address->{AddressManager::COLUMN_CITY};
                    $addressArray[AddressManager::COLUMN_REGION.$i]= $address->{AddressManager::COLUMN_REGION};
                    $addressArray[AddressManager::COLUMN_ZIP_CODE.$i]=$address->{AddressManager::COLUMN_ZIP_CODE};
                    $addressArray[AddressManager::COLUMN_STATE.$i] = $address->{AddressManager::COLUMN_STATE};
                    $identityArray = array_merge($identityArray,$addressArray);
                    $i++;
                }
            } 
         }
        
        $form->setDefaults($identityArray);

        $form->onSuccess[] = function(Form $form, $values){
            $filename = $this->userManager->get($this->user)[UserManager::COLUMN_ICON];
            if(!empty($values["icon"])){
                $filename = $values["icon"]->getName();
                $path = __DIR__ . "/../../www/images/icons/" . $filename;
                while(file_exists($path)) {
                    $filename = "0".$filename;
                    $path = __DIR__ . "/../../www/images/icons/" . $filename;
                }
                $values["icon"]->move($path);
            }
            if ($activeAddress->{AddressManager::COLUMN_STREET}!= $values[AddressManager::COLUMN_STREET]){
                
                if(!empty($activeAddressId = $this->addressManager->getAddressIdByStreet($values[AddressManager::COLUMN_STREET], $userId))){
                    $this->userManager->editActiveAddress($userId, $activeAddressId);
                } else {
                  $completAddress = [AddressManager::COLUMN_STREET => $values[AddressManager::COLUMN_STREET],
                                               AddressManager::COLUMN_CITY => $values[AddressManager::COLUMN_CITY],
                                               AddressManager::COLUMN_REGION => $values[AddressManager::COLUMN_REGION],
                                               AddressManager::COLUMN_ZIP_CODE => $values[AddressManager::COLUMN_ZIP_CODE],
                                               AddressManager::COLUMN_STATE => $values[AddressManager::COLUMN_STATE],
                                               AddressManager::COLUMN_LATITUDE => $values[AddressManager::COLUMN_LATITUDE],
                                               AddressManager::COLUMN_LONGITUDE => $values[AddressManager::COLUMN_LONGITUDE],
                                               AddressManager::COLUMN_USER => $userId];
                            $activeAddressId = $this->addressManager->addAddress($completAddress, $userId);
                            $this->userManager->editActiveAddress($userId, $activeAddressId);
                }    
            }
            
            $completAddress = [AddressManager::COLUMN_STREET => $values[AddressManager::COLUMN_STREET],
                               AddressManager::COLUMN_CITY => $values[AddressManager::COLUMN_CITY],
                               AddressManager::COLUMN_REGION => $values[AddressManager::COLUMN_REGION],
                               AddressManager::COLUMN_ZIP_CODE => $values[AddressManager::COLUMN_ZIP_CODE],
                               AddressManager::COLUMN_STATE => $values[AddressManager::COLUMN_STATE],
                               AddressManager::COLUMN_LATITUDE => $values[AddressManager::COLUMN_LATITUDE],
                               AddressManager::COLUMN_LONGITUDE => $values[AddressManager::COLUMN_LONGITUDE],
                               AddressManager::COLUMN_USER => $userId];
            $activeAddressId = $this->addressManager->addAddress($completAddress, $userId);
            
            if ($countAddresses>1){
                for($i=2;$i<$countAdresses+2;$i++){
                    $completAddress = [AddressManager::COLUMN_STREET => $values[AddressManager::COLUMN_STREET.$i],
                               AddressManager::COLUMN_CITY => $values[AddressManager::COLUMN_CITY.$i],
                               AddressManager::COLUMN_REGION => $values[AddressManager::COLUMN_REGION.$i],
                               AddressManager::COLUMN_ZIP_CODE => $values[AddressManager::COLUMN_ZIP_CODE.$i],
                               AddressManager::COLUMN_STATE => $values[AddressManager::COLUMN_STATE.$i],
                               AddressManager::COLUMN_LATITUDE => $values[AddressManager::COLUMN_LATITUDE.$i],
                               AddressManager::COLUMN_LONGITUDE => $values[AddressManager::COLUMN_LONGITUDE.$i],
                               AddressManager::COLUMN_USER => $userId];
                               if(empty($this->getAddressIdByStreet($completAddress[self::COLUMN_STREET.$i], $userId))){
                                 $this->addressManager->addAddress($completAddress, $userId);
                               }
                }
            }
            $properties =[
                UserManager::COLUMN_PHONE => $values[UserManager::COLUMN_PHONE],
                UserManager::COLUMN_FIRSTNAME => $values[UserManager::COLUMN_FIRSTNAME],
                UserManager::COLUMN_LASTNAME => $values[UserManager::COLUMN_LASTNAME],
                UserManager::COLUMN_TIME => $values[UserManager::COLUMN_TIME],
                UserManager::COLUMN_SEX => $values[UserManager::COLUMN_SEX],
                UserManager::COLUMN_ICON => $filename,
                UserManager::COLUMN_NOTE => $values[UserManager::COLUMN_NOTE],
                UserManager::COLUMN_ACTIVE_ADDRESS_ID => $activeAddressId
            ];
            $this->userManager->edit($this->user, $properties);
        };
	    return $form;
    }

	/**
	 * @return Form
	 */
	public function createRegistrationForm(callable $onSuccess)
	{       $addresses = [];
		$form = $this->createForm(true);

		$form->onSuccess[] = function (Form $form, $values) use ($onSuccess) {
			try {
                            $values = $form->getHttpData();
                           
                            $filename = null;
                            if(!empty($values["icon"])){
                                $filename = $values["icon"]->getName();
                                $path = __DIR__ . "/../../www/images/icons/" . $filename;
                                while(file_exists($path)) {
                                    $filename = "0".$filename;
                                    $path = __DIR__ . "/../../www/images/icons/" . $filename;
                                }
                                $values["icon"]->move($path);
                            }

			    $array = [UserManager::COLUMN_NAME => $values[UserManager::COLUMN_NAME],
                                      UserManager::COLUMN_EMAIL => $values[UserManager::COLUMN_EMAIL],
                                      UserManager::COLUMN_PASSWORD_HASH => $values[UserManager::COLUMN_PASSWORD_HASH],
                                      UserManager::COLUMN_PHONE => $values[UserManager::COLUMN_PHONE],
                                      UserManager::COLUMN_FIRSTNAME => $values[UserManager::COLUMN_FIRSTNAME],
                                      UserManager::COLUMN_LASTNAME => $values[UserManager::COLUMN_LASTNAME],
                                      UserManager::COLUMN_TIME => $values[UserManager::COLUMN_TIME],
                                      UserManager::COLUMN_SEX => $values[UserManager::COLUMN_SEX],
                                      UserManager::COLUMN_ICON => $filename,
                                      UserManager::COLUMN_ACTIVE_ADDRESS_ID => null,
                                      UserManager::COLUMN_NOTE => $values[UserManager::COLUMN_NOTE]];
                            $userId = $this->userManager->add($array);
                            
                            $completAddress = [AddressManager::COLUMN_STREET => $values[AddressManager::COLUMN_STREET],
                                               AddressManager::COLUMN_CITY => $values[AddressManager::COLUMN_CITY],
                                               AddressManager::COLUMN_REGION => $values[AddressManager::COLUMN_REGION],
                                               AddressManager::COLUMN_ZIP_CODE => $values[AddressManager::COLUMN_ZIP_CODE],
                                               AddressManager::COLUMN_STATE => $values[AddressManager::COLUMN_STATE],
                                               AddressManager::COLUMN_LATITUDE => $values[AddressManager::COLUMN_LATITUDE],
                                               AddressManager::COLUMN_LONGITUDE => $values[AddressManager::COLUMN_LONGITUDE],
                                               AddressManager::COLUMN_USER => $userId];
                            $activeAddressId = $this->addressManager->addAddress($completAddress, $userId);
                            
                            if(!empty($values['countAddresses'])){
                                $countAdresses = intval($values['countAddresses']);
                                for($i=1;$i<$countAdresses+1;$i++){
                                    $completAddress = [AddressManager::COLUMN_STREET => $values[AddressManager::COLUMN_STREET.$i],
                                               AddressManager::COLUMN_CITY => $values[AddressManager::COLUMN_CITY.$i],
                                               AddressManager::COLUMN_REGION => $values[AddressManager::COLUMN_REGION.$i],
                                               AddressManager::COLUMN_ZIP_CODE => $values[AddressManager::COLUMN_ZIP_CODE.$i],
                                               AddressManager::COLUMN_STATE => $values[AddressManager::COLUMN_STATE.$i],
                                               AddressManager::COLUMN_LATITUDE => $values[AddressManager::COLUMN_LATITUDE.$i],
                                               AddressManager::COLUMN_LONGITUDE => $values[AddressManager::COLUMN_LONGITUDE.$i],
                                               AddressManager::COLUMN_USER => $userId];
                                               $this->addressManager->addAddress($completAddress, $userId);
                                }
                            }
                            $this->userManager->editActiveAddress($userId, $activeAddressId);
                            $this->registrationManager->sendRegisterEmail($values[UserManager::COLUMN_NAME],
                            $values[UserManager::COLUMN_PASSWORD_HASH], $values[UserManager::COLUMN_EMAIL]);
			} catch (DuplicateNameException $e) {
				$form[UserManager::COLUMN_NAME]->addError($this->translator->translate('messages.registrationForm.busy username'));
				return;
			}
                       
			$onSuccess();
		};

		return $form;
	}
        
        protected function createForm($registration = false, $userId = null){
            $form = $this->create();
            $form->addText(UserManager::COLUMN_FIRSTNAME, $this->translator->translate ('messages.user.'.UserManager::COLUMN_FIRSTNAME))
                 ->setRequired(true)
                 ->setHtmlAttribute('class','fancyform');
            $form->addText(UserManager::COLUMN_LASTNAME, $this->translator->translate ('messages.user.'.UserManager::COLUMN_LASTNAME))
                 ->setRequired(true)
                 ->setHtmlAttribute('class','fancyform');   
            $form->addText(AddressManager::COLUMN_STREET, $this->translator->translate ('messages.address.'.AddressManager::COLUMN_STREET))
                         ->setHtmlAttribute('title',$this->translator->translate("messages.editForm.choose from suggest"))   
                         ->setHtmlAttribute("id", "text-address")->setRequired()
                         ->setHtmlAttribute('class','formPlan fancyform street');
            $form->addText(AddressManager::COLUMN_CITY, $this->translator->translate ('messages.address.'.AddressManager::COLUMN_CITY))
                 ->setHtmlAttribute("readonly", "readonly")
                 ->setRequired()
                 ->setHtmlAttribute("id", "city")
                 ->setHtmlAttribute('class','formPlan fancyform city');   
            $form->addText(AddressManager::COLUMN_REGION,$this->translator->translate ('messages.address.'.AddressManager::COLUMN_REGION))
                 ->setHtmlAttribute("readonly", "readonly")
                 ->setHtmlAttribute("id", "region")   
                 ->setHtmlAttribute('class', 'formPlan');
            $form->addText(AddressManager::COLUMN_ZIP_CODE, $this->translator->translate ('messages.address.'.AddressManager::COLUMN_ZIP_CODE))
                 ->setRequired()
                 ->setHtmlAttribute("id", "zip-code")
                 ->setHtmlAttribute('class','formPlan fancyform zipCode');   
            $form->addHidden(AddressManager::COLUMN_LATITUDE)
                 ->setRequired()
                 ->setHtmlAttribute("id", "latitude")   
                 ->setHtmlAttribute('class', 'formPlan'); 
            $form->addHidden(AddressManager::COLUMN_LONGITUDE)
                 ->setRequired()
                 ->setHtmlAttribute("id", "longitude")   
                 ->setHtmlAttribute('class', 'formPlan');
            $form->addText(AddressManager::COLUMN_STATE, $this->translator->translate ('messages.address.'.AddressManager::COLUMN_STATE))
                 ->setHtmlAttribute("readonly", "readonly")
                 ->setHtmlAttribute("id", "state")   
                 ->setRequired()
                 ->setHtmlAttribute('class', 'formPlan state');
            $form->addButton('addAddress', 'addAddress')
                 ->setHtmlAttribute("hidden", "true")
                 ->setHtmlAttribute("id", "addAddress")
                 ->setHtmlAttribute('class','formPlan fancyform addAddress ');   
            $form->addButton('removeAddress', 'removedAddress')
                 ->setHtmlAttribute("hidden", "true")
                 ->setHtmlAttribute("id", "removeAddress")
                 ->setHtmlAttribute("onclick", "clearAddress")   
                 ->setHtmlAttribute('class','formPlan fancyform removeAddress');   
            $form->addText(AddressManager::COLUMN_ID, '')
                 ->setHtmlAttribute("hidden", "true")
                 ->setRequired()
                 ->setHtmlAttribute("id", "activeAddress")
                 ->setHtmlAttribute('class','formPlan fancyform activeAddress');
            $form->addButton('activeButton', 'Aktivní adresa')
                 ->setHtmlAttribute("id", "activeButton")
                 ->setHtmlAttribute('class','formPlan fancyform activeButton'); 
            
            
            if ($registration) {
                $form->addEmail(UserManager::COLUMN_EMAIL, $this->translator->translate ('messages.user.'.UserManager::COLUMN_EMAIL))
		     ->setRequired($this->translator->translate("messages.editForm.fill your email"))
                     ->setHtmlAttribute('class','fancyform');   
            } else {
                $countAddresses = $this->addressManager->countAddresses($userId);
                if($countAddresses>1){
                    for($i=2;$i<$countAddresses+1;$i++){
                        $form->addText(AddressManager::COLUMN_STREET.$i, $this->translator->translate ('messages.address.'.AddressManager::COLUMN_STREET))
                         ->setHtmlAttribute('title',$this->translator->translate("messages.editForm.choose from suggest"))   
                         ->setHtmlAttribute("id", "text-address".$i)->setRequired()
                         ->setHtmlAttribute("readonly", "readonly")
                         ->setHtmlAttribute('class','formPlan fancyform street');       
                        $form->addText(AddressManager::COLUMN_CITY.$i, $this->translator->translate ('messages.address.'.AddressManager::COLUMN_CITY).$i)
                             ->setHtmlAttribute("readonly", "readonly")
                             ->setHtmlAttribute("id", "city".$i)   
                             ->setRequired()
                             ->setHtmlAttribute('class', 'formPlan city');
                        $form->addText(AddressManager::COLUMN_REGION.$i,$this->translator->translate ('messages.address.'.AddressManager::COLUMN_REGION).$i)
                             ->setHtmlAttribute("readonly", "readonly")
                             ->setHtmlAttribute("id", "region".$i)   
                             ->setHtmlAttribute('class', 'formPlan');
                        $form->addText(AddressManager::COLUMN_ZIP_CODE.$i, $this->translator->translate ('messages.address.'.AddressManager::COLUMN_ZIP_CODE).$i)
                             ->setHtmlAttribute("id", "zip-code".$i)
                             ->setHtmlAttribute('class','formPlan fancyform zipCode');   
                        $form->addHidden(AddressManager::COLUMN_LATITUDE.$i)
                             ->setRequired()
                             ->setHtmlAttribute("id", "latitude".$i)   
                             ->setHtmlAttribute('class', 'formPlan'); 
                        $form->addHidden(AddressManager::COLUMN_LONGITUDE.$i)
                             ->setRequired()
                             ->setHtmlAttribute("id", "longitude".$i)   
                             ->setHtmlAttribute('class', 'formPlan'); 
                        $form->addText(AddressManager::COLUMN_STATE.$i, $this->translator->translate ('messages.address.'.AddressManager::COLUMN_STATE).$i)
                             ->setHtmlAttribute("readonly", "readonly")
                             ->setHtmlAttribute("id", "state".$i)   
                             ->setRequired()
                             ->setHtmlAttribute('class', 'formPlan state');
                        $form->addButton('addAddress'.$i, 'addAddress')
                             ->setHtmlAttribute("hidden", "true")
                             ->setHtmlAttribute("id", "addAddress".$i)
                             ->setHtmlAttribute('class','formPlan fancyform addAddress ');   
                        $form->addButton('removeAddress'.$i, 'removedAddress'.$i)
                             
                             ->setHtmlAttribute("id", "removeAddress".$i)
                             ->setHtmlAttribute("onclick", "removeAddress")   
                             ->setHtmlAttribute('class','formPlan fancyform removeAddress');
                        $form->addText(AddressManager::COLUMN_ID.$i, '')
                             ->setHtmlAttribute("hidden", "true")
                             ->setRequired()
                             ->setHtmlAttribute("id", "activeAddress".$i)
                             ->setHtmlAttribute('class','formPlan fancyform activeAddress');
                        $form->addButton('activeButton'.$i, 'Nastavit jako aktivní adresu')
                              ->setHtmlAttribute("id", "activeButton.$i")
                             ->setHtmlAttribute('class','formPlan fancyform activeButton'); 
                                }
                                }
            }
            $form->addText( UserManager::COLUMN_PHONE ,$this->translator->translate ('messages.user.'.UserManager::COLUMN_PHONE))
                 ->setHtmlAttribute('class','fancyform');   
            $form->addText( UserManager::COLUMN_TIME , $this->translator->translate ('messages.user.'.UserManager::COLUMN_TIME))
                 ->setHtmlAttribute('class','fancyform');   
            $form->addSelect(UserManager::COLUMN_SEX, $this->translator->translate ('messages.user.'.UserManager::COLUMN_SEX))->setRequired('test message')
                 ->setHtmlAttribute('class','fancyform');   
            $sex = [0 => $this->translator->translate("messages.editForm.man"), 1 =>  $this->translator->translate("messages.editForm.woman")];
            $form[UserManager::COLUMN_SEX]->setItems($sex);
            if ($registration){
                $form->addText(UserManager::COLUMN_NAME, $this->translator->translate ('messages.user.'.UserManager::COLUMN_NAME))->setRequired(true)
                     ->setHtmlAttribute('class','fancyform');   
            }
            $form->addUpload(UserManager::COLUMN_ICON,$this->translator->translate ('messages.user.'.UserManager::COLUMN_ICON))
                 ->setHtmlAttribute('class','fancyform');   
            if ($registration) {
                $form->addPassword(UserManager::COLUMN_PASSWORD_HASH, $this->translator->translate ('messages.user.'.UserManager::COLUMN_PASSWORD_HASH))
		     ->setRequired( $this->translator->translate("messages.editForm.choose a password"))
		     ->addRule($form::MIN_LENGTH, $this->translator->translate('messages.registrationForm.minLength',[ 'min' => self::PASSWORD_MIN_LENGTH]), self::PASSWORD_MIN_LENGTH)
                     ->setHtmlAttribute('class','fancyform');   
            }
            $form->addTextArea(UserManager::COLUMN_NOTE,$this->translator->translate ('messages.user.'.UserManager::COLUMN_NOTE))
                 ->setHtmlAttribute('class','fancyform');   
            $form->addHidden('countAddresses');
            if ($registration) {
                $form->addSubmit('register',$this->translator->translate('messages.registrationForm.to register'))
                     ->setHtmlAttribute('class','fancyform');   
            } else {
                $form->addSubmit('changeInformation', $this->translator->translate("messages.editForm.change data"))
                     ->setHtmlAttribute('class','fancyform');   
            }
            return $form;
        }
}
