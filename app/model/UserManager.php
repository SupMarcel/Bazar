<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\Database\Explorer;
use Contributte\Translation\Translator;


/**
 * Users management.
 */
class UserManager extends BaseManager implements Nette\Security\IAuthenticator
{
	use Nette\SmartObject;


	const
		TABLE_NAME = 'user',
		COLUMN_ID = 'id',
		COLUMN_NAME = 'user_name',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_EMAIL = 'email',
                COLUMN_PHONE = 'phone',
                COLUMN_FIRSTNAME = 'firstname',
                COLUMN_LASTNAME = 'lastname',
                
                COLUMN_TIME = 'opening_hours',
                COLUMN_NOTE = 'note',
                COLUMN_ROLE = 'role',
                COLUMN_SEX = 'genders',
                COLUMN_ICON = 'icon',
                COLUMN_EMAIL_SUBSCRIPTION = 'email_subscription',
                COLUMN_ACTIVE_ADDRESS_ID = 'active_address_id',
                COLUMN_LANGUAGE = 'language';
        
         /** @var Passwords */
    private Passwords $passwords;
    
     /** @var Contributte\Translation\Translator */
    public $translator;


    public function __construct(Explorer $database, Passwords $passwords, Translator $translator)
    {
        parent::__construct($database);
        $this->passwords = $passwords;
        $this->translator = $translator;
    }



	

    /**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials) : Nette\Security\IIdentity
	{
		list($username, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_NAME, $username)
			->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException($this->translator->translate("messages.UserManager.incorrect_user_name"), self::IDENTITY_NOT_FOUND);

		} elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException($this->translator->translate("messages.UserManager.incorrect_password"), self::INVALID_CREDENTIAL);

		} elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update([
				self::COLUMN_PASSWORD_HASH =>$this->passwords->hash($password),
			]);
		}

		$userData = $row->toArray();
		unset($userData[self::COLUMN_PASSWORD_HASH]);
                bdump(new Nette\Security\SimpleIdentity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $userData));

		return new Nette\Security\SimpleIdentity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $userData);
	}

    /**
     * Adds a new user.
     * @param  array $properties
     * @return void
     * @throws DuplicateNameException
     */
    public function add($properties)
    {
        try {
            $row = $this->database->table(self::TABLE_NAME)->insert([
                self::COLUMN_NAME => isset($properties[self::COLUMN_NAME]) ? htmlspecialchars(trim($properties[self::COLUMN_NAME])) : '',
                self::COLUMN_PASSWORD_HASH => isset($properties[self::COLUMN_PASSWORD_HASH]) ? $this->passwords->hash($properties[self::COLUMN_PASSWORD_HASH]) : '',
                self::COLUMN_EMAIL => isset($properties[self::COLUMN_EMAIL]) ? htmlspecialchars(trim($properties[self::COLUMN_EMAIL])) : '',
                self::COLUMN_PHONE => isset($properties[self::COLUMN_PHONE]) ? htmlspecialchars(trim($properties[self::COLUMN_PHONE])) : '',
                self::COLUMN_FIRSTNAME => isset($properties[self::COLUMN_FIRSTNAME]) ? htmlspecialchars(trim($properties[self::COLUMN_FIRSTNAME])) : '',
                self::COLUMN_LASTNAME => isset($properties[self::COLUMN_LASTNAME]) ? htmlspecialchars(trim($properties[self::COLUMN_LASTNAME])) : '',
                self::COLUMN_TIME => isset($properties[self::COLUMN_TIME]) ? $properties[self::COLUMN_TIME] : '',
                self::COLUMN_NOTE => isset($properties[self::COLUMN_NOTE]) ? htmlspecialchars(trim($properties[self::COLUMN_NOTE])) : '',
                self::COLUMN_SEX => isset($properties[self::COLUMN_SEX]) ? htmlspecialchars(trim($properties[self::COLUMN_SEX])) : '',
                self::COLUMN_ICON => isset($properties[self::COLUMN_ICON]) ? htmlspecialchars(trim($properties[self::COLUMN_ICON])) : '',
                self::COLUMN_ROLE => "NORMALUSER"
            ]);

            return $row->id;
        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            throw new DuplicateNameException;
        }
    }


    public function edit($id, $properties) {
        $user = $this->database->table(self::TABLE_NAME)->get($id);
        if (!empty($user)) {
            try {
                $user->update([
                    self::COLUMN_PHONE => isset($properties[self::COLUMN_PHONE]) ? htmlspecialchars(trim($properties[self::COLUMN_PHONE])) : '',
                    self::COLUMN_FIRSTNAME => isset($properties[self::COLUMN_FIRSTNAME]) ? htmlspecialchars(trim($properties[self::COLUMN_FIRSTNAME])) : '',
                    self::COLUMN_LASTNAME => isset($properties[self::COLUMN_LASTNAME]) ? htmlspecialchars(trim($properties[self::COLUMN_LASTNAME])) : '',
                    self::COLUMN_TIME => isset($properties[self::COLUMN_TIME]) ? $properties[self::COLUMN_TIME] : '',
                    self::COLUMN_NOTE => isset($properties[self::COLUMN_NOTE]) ? htmlspecialchars(trim($properties[self::COLUMN_NOTE])) : '',
                    self::COLUMN_SEX => isset($properties[self::COLUMN_SEX]) ? htmlspecialchars(trim($properties[self::COLUMN_SEX])) : '',
                    self::COLUMN_ICON => isset($properties[self::COLUMN_ICON]) ? htmlspecialchars(trim($properties[self::COLUMN_ICON])) : '',
                    self::COLUMN_ROLE => "NORMALUSER"
                    ]);
            } catch (Nette\Database\Exception $e) {
                throw new Nette\Database\Exception($this->translator->translate("messages.UserManager.no_change_data"));
              }
        } else {
            throw new Nette\Neon\Exception($this->translator->translate("messages.UserManager.incorect_user"));
        }
    }


    
    public function editLanguage ($id, $language){
        $user = $this->database->table(self::TABLE_NAME)->get($id);
        if (!empty($user)){
            try {    
                $user->update([
                        self::COLUMN_LANGUAGE => $language
                             ]);
            }catch (Nette\Database\Exception $e) {
                throw new Nette\Database\Exception($this->translator->translate("messages.UserManager.no_change_data"));
             }
        }else {
            throw new Nette\Neon\Exception($this->translator->translate("messages.UserManager.incorect_user"));
        }
    }
    
    public function editActiveAddress($id, $activeAddressId){
	    $user = $this->database->table(self::TABLE_NAME)->get($id);
	    if (!empty($user)){
                try{
                    $user->update([
                        self::COLUMN_ACTIVE_ADDRESS_ID => $activeAddressId
                             ]);
                }catch (Nette\Database\Exception $e) {
                throw new Nette\Database\Exception($this->translator->translate("messages.UserManager.no_change_data"));
                 }
            }else{
                throw new Nette\Neon\Exception($this->translator->translate("messages.UserManager.incorect_user"));
            }
    }

    public function cancelEmailSubscription($id){
        $user = $this->database->table(self::TABLE_NAME)->get($id);
        if(!empty($user)){
            try {
                $user->update([
                    self::COLUMN_EMAIL_SUBSCRIPTION => 0
                ]);
            }catch (Nette\Database\Exception $e) {
                throw new Nette\Database\Exception($this->translator->translate("messages.UserManager.no_change_data"));
             }
        } else {
            throw new Nette\Neon\Exception($this->translator->translate("messages.UserManager.incorect_user"));
        }
    }
}



class DuplicateNameException extends \Exception
{
    
}
