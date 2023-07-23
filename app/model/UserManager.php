<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\Database\Explorer;
use Contributte\Translation\Translator;
use Tracy\ILogger;
use Nette\Security\User;

/**
 * Users management.
 */
class UserManager extends BaseManager implements Nette\Security\IAuthenticator {

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

    public function __construct(Explorer $database, ILogger $logger, Passwords $passwords, Translator $translator) {
        parent::__construct($database, $logger);
        $this->passwords = $passwords;
        $this->translator = $translator;
    }

    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials): Nette\Security\IIdentity {
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
                self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
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
    public function add($properties) {
        try {
            $sanitizedProperties = $this->sanitizeProperties($properties);
            $sanitizedProperties[self::COLUMN_PASSWORD_HASH] = isset($properties[self::COLUMN_PASSWORD_HASH]) ? $this->passwords->hash($properties[self::COLUMN_PASSWORD_HASH]) : '';
            $sanitizedProperties[self::COLUMN_ROLE] = "NORMALUSER";

            $row = $this->database->table(self::TABLE_NAME)->insert($sanitizedProperties);

            return $row->id;
        } catch (PDOException $e) {
            $this->logError('Chyba při registraci uživatele: ' . $e->getMessage());
            $this->presenter->flashMessage($this->translator->translate("messages.UserManager.error_register"), 'error');
        }
    }

    private function sanitizeProperties($properties) {
        $columns = [
            self::COLUMN_NAME,
            self::COLUMN_EMAIL,
            self::COLUMN_PHONE,
            self::COLUMN_FIRSTNAME,
            self::COLUMN_LASTNAME,
            self::COLUMN_TIME,
            self::COLUMN_NOTE,
            self::COLUMN_SEX,
            self::COLUMN_ICON
        ];

        $sanitizedProperties = [];
        foreach ($columns as $column) {
            $sanitizedProperties[$column] = isset($properties[$column]) ? htmlspecialchars(trim($properties[$column])) : '';
        }

        return $sanitizedProperties;
    }

    private function hasPermission(User $currentUser, $id): bool {
        $currentUserId = $currentUser->getId();
        return $currentUser->isInRole('admin') || $currentUserId == $id;
    }

    public function edit($id, $properties, User $currentUser) {
        if ($this->hasPermission($currentUser, $id)) {
            $user = $this->database->table(self::TABLE_NAME)->get($id);
            if (!empty($user)) {
                try {
                    $updateData = [
                        self::COLUMN_PHONE => isset($properties[self::COLUMN_PHONE]) ? htmlspecialchars(trim($properties[self::COLUMN_PHONE])) : '',
                        self::COLUMN_FIRSTNAME => isset($properties[self::COLUMN_FIRSTNAME]) ? htmlspecialchars(trim($properties[self::COLUMN_FIRSTNAME])) : '',
                        self::COLUMN_LASTNAME => isset($properties[self::COLUMN_LASTNAME]) ? htmlspecialchars(trim($properties[self::COLUMN_LASTNAME])) : '',
                        self::COLUMN_TIME => isset($properties[self::COLUMN_TIME]) ? $properties[self::COLUMN_TIME] : '',
                        self::COLUMN_NOTE => isset($properties[self::COLUMN_NOTE]) ? htmlspecialchars(trim($properties[self::COLUMN_NOTE])) : '',
                        self::COLUMN_SEX => isset($properties[self::COLUMN_SEX]) ? htmlspecialchars(trim($properties[self::COLUMN_SEX])) : '',
                        self::COLUMN_ICON => isset($properties[self::COLUMN_ICON]) ? htmlspecialchars(trim($properties[self::COLUMN_ICON])) : '',
                        self::COLUMN_ROLE => "NORMALUSER",
                        self::COLUMN_EMAIL_SUBSCRIPTION => isset($properties[self::COLUMN_EMAIL_SUBSCRIPTION]) ? htmlspecialchars(trim($properties[self::COLUMN_EMAIL_SUBSCRIPTION])) : 0,
                    ];
                    if (isset($properties[self::COLUMN_PASSWORD_HASH])) {
                        $updateData[self::COLUMN_PASSWORD_HASH] = $this->passwords->hash($properties[self::COLUMN_PASSWORD_HASH]);
                    }
                    $user->update($updateData);
                } catch (PDOException $e) {
                    $this->logError('Chyba při zápisu změny údajů uživatele: ' . $e->getMessage());
                    $this->getPresenter()->flashMessage($this->translator->translate("messages.UserManager.error_edit"), 'error');
                }
            } else {
                throw new Nette\Neon\Exception($this->translator->translate("messages.UserManager.incorect_user"));
            }
        } else {
            $this->getPresenter()->flashMessage($this->translator->translate('messages.UserManager.not_permision_delete_user'), 'error');
        }
    }

    public function editLanguage($id, $language, User $currentUser) {
        if ($this->hasPermission($currentUser, $id)) {
            $user = $this->database->table(self::TABLE_NAME)->get($id);
            if (!empty($user)) {
                try {
                    $user->update([
                        self::COLUMN_LANGUAGE => $language
                    ]);
                } catch (PDOException $e) {
                    $this->logError('Chyba při editaci jazyka uživatele: ' . $e->getMessage());
                    $this->presenter->flashMessage($this->translator->translate("messages.UserManager.error_language"), 'error');
                }
            } else {
                throw new Nette\Neon\Exception($this->translator->translate("messages.UserManager.incorect_user"));
            }
        } else {
            $this->getPresenter()->flashMessage($this->translator->translate('messages.UserManager.not_permision_delete_user'), 'error');
        }
    }

    public function editActiveAddress($id, $activeAddressId, User $currentUser) {
        if ($this->hasPermission($currentUser, $id)) {
            $user = $this->database->table(self::TABLE_NAME)->get($id);
            if (!empty($user)) {
                try {
                    $user->update([
                        self::COLUMN_ACTIVE_ADDRESS_ID => $activeAddressId
                    ]);
                } catch (PDOException $e) {
                    $this->logError('Chyba při změně aktivní adresy: ' . $e->getMessage());
                    $this->presenter->flashMessage($this->translator->translate("messages.UserManager.error_active_address"), 'error');
                }
            } else {
                throw new Nette\Neon\Exception($this->translator->translate("messages.UserManager.incorect_user"));
            }
        } else {
            $this->getPresenter()->flashMessage($this->translator->translate('messages.UserManager.not_permision_delete_user'), 'error');
        }
    }

    public function cancelEmailSubscription($id, User $currentUser) {
        if ($this->hasPermission($currentUser, $id)) {
            $user = $this->database->table(self::TABLE_NAME)->get($id);
            if (!empty($user)) {
                try {
                    $user->update([
                        self::COLUMN_EMAIL_SUBSCRIPTION => 0
                    ]);
                } catch (PDOException $e) {
                    $this->logError('Chyba při změně aktivní adresy: ' . $e->getMessage());
                    $this->presenter->flashMessage($this->translator->translate("messages.UserManager.error_active_address"), 'error');
                }
            } else {
                throw new Nette\Neon\Exception($this->translator->translate("messages.UserManager.incorect_user"));
            }
        } else {
            $this->getPresenter()->flashMessage($this->translator->translate('messages.UserManager.not_permision_delete_user'), 'error');
        }
    }

    public function deleteUser($id, User $currentUser): void {
        if ($this->hasPermission($currentUser, $id)) {
            try {
                $this->database->table(self::TABLE_NAME)->where('id', $id)->delete();
            } catch (\PDOException $e) {
                // Zalogování chyby
                $this->logError('Chyba při mazání uživatele: ' . $e->getMessage());
                $this->getPresenter()->flashMessage($this->translator->translate('messages.UserManager.error_deleting_user'), 'error');
            }
        } else {
            $this->getPresenter()->flashMessage($this->translator->translate('messages.UserManager.not_permision_delete_user'), 'error');
        }
    }
}

class DuplicateNameException extends \Exception {
    
}
