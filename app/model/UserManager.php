<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\Database\Explorer;


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


    public function __construct(Explorer $database, Passwords $passwords)
    {
        parent::__construct($database);
        $this->passwords = $passwords;
    }



	

    /**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_NAME, $username)
			->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		} elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update([
				self::COLUMN_PASSWORD_HASH =>$this->passwords->hash($password),
			]);
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
	}


	/**
	 * Adds new user.
	 * @param  array
	 * @return void
	 * @throws DuplicateNameException
	 */
	public function add($properties)
	{
		try {
                    $row = $this->database->table(self::TABLE_NAME)->insert([
                        self::COLUMN_NAME => $properties[self::COLUMN_NAME],
                        self::COLUMN_PASSWORD_HASH => $this->passwords->hash($properties[self::COLUMN_PASSWORD_HASH]),
                        self::COLUMN_EMAIL => $properties[self::COLUMN_EMAIL],
                        self::COLUMN_PHONE => $properties[self::COLUMN_PHONE],
                        self::COLUMN_FIRSTNAME => $properties[self::COLUMN_FIRSTNAME],
                        self::COLUMN_LASTNAME => $properties[self::COLUMN_LASTNAME],
                        
                        self::COLUMN_TIME => $properties[self::COLUMN_TIME],
                        self::COLUMN_NOTE => $properties[self::COLUMN_NOTE],
                        self::COLUMN_SEX => $properties[self::COLUMN_SEX],
                        self::COLUMN_ICON => $properties[self::COLUMN_ICON],
                        self::COLUMN_ROLE => "NORMALUSER"
                    ]);
                    return $row->id;
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}

    public function edit($id, $properties){
	    $user = $this->database->table(self::TABLE_NAME)->get($id);
	    if(!empty($user)){
            $user->update([
                self::COLUMN_PHONE => $properties[self::COLUMN_PHONE],
                self::COLUMN_FIRSTNAME => $properties[self::COLUMN_FIRSTNAME],
                self::COLUMN_LASTNAME => $properties[self::COLUMN_LASTNAME],
                self::COLUMN_CITY => BaseManager::CITY,
                self::COLUMN_TIME => $properties[self::COLUMN_TIME],
                self::COLUMN_NOTE => $properties[self::COLUMN_NOTE],
                self::COLUMN_SEX => $properties[self::COLUMN_SEX],
                self::COLUMN_ICON => $properties[self::COLUMN_ICON],
                self::COLUMN_ROLE => "NORMALUSER"
            ]);
            } else {
	        throw new Nette\Neon\Exception("Uživatel s tímto ID nebyl nalezen.");
              } 
    }
    
    
    public function editLanguage ($id, $language){
        $user = $this->database->table(self::TABLE_NAME)->get($id);
	$user->update([
                self::COLUMN_LANGUAGE => $language
                     ]);
    }
    
    public function editActiveAddress($id, $activeAddressId){
	    $user = $this->database->table(self::TABLE_NAME)->get($id);
	    
            $user->update([
                self::COLUMN_ACTIVE_ADDRESS_ID => $activeAddressId
                     ]);        
    }

    public function cancelEmailSubscription($id){
        $user = $this->database->table(self::TABLE_NAME)->get($id);
        if(!empty($user)){
            $user->update([
                self::COLUMN_EMAIL_SUBSCRIPTION => 0
            ]);
        } else {
            throw new Nette\Neon\Exception("Uživatel s tímto ID nebyl nalezen.");
        }
    }
}



class DuplicateNameException extends \Exception
{
    
}
