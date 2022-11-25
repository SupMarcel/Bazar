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
		TABLE_NAME = 'uzivatel',
		COLUMN_ID = 'id',
		COLUMN_NAME = 'uzivatelskeJmeno',
		COLUMN_PASSWORD_HASH = 'heslo',
		COLUMN_EMAIL = 'email',
        COLUMN_PHONE = 'telefon',
        COLUMN_FIRSTNAME = 'jmeno',
        COLUMN_LASTNAME = 'prijmeni',
        COLUMN_CITY = 'obec',
        COLUMN_TIME = 'doba',
        COLUMN_NOTE = 'poznamka',
		COLUMN_ROLE = 'role',
        COLUMN_SEX = 'pohlavi',
        COLUMN_ICON = 'ikona',
        COLUMN_EMAIL_SUBSCRIPTION = 'odberEmailu';
        
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
                    $this->database->table(self::TABLE_NAME)->insert([
                        self::COLUMN_NAME => $properties[self::COLUMN_NAME],
                        self::COLUMN_PASSWORD_HASH => $this->passwords->hash($properties["heslo"]),
                        self::COLUMN_EMAIL => $properties[self::COLUMN_EMAIL],
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
