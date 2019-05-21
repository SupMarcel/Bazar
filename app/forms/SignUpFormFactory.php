<?php

namespace App\Forms;

use App\Model;
use Nette;
use Nette\Application\UI\Form;


class SignUpFormFactory
{
	use Nette\SmartObject;

	const PASSWORD_MIN_LENGTH = 8;

	/** @var FormFactory */
	private $factory;

	/** @var Model\UserManager */
	private $userManager;
	/** @var  Model\RegistrationManager */
	private $registrationManager;
	private $user;


	public function __construct(FormFactory $factory, Model\UserManager $userManager,
                                Model\RegistrationManager $registrationManager)
	{
		$this->factory = $factory;
		$this->userManager = $userManager;
		$this->registrationManager = $registrationManager;
		$this->user = null;
	}


	public function createEditForm($user){
	    $form = $this->factory->create();
	    $this->user = $user;
        $form->addText('phone', 'Telefon');
        $form->addText('firstname', 'Jméno')->setRequired(true);
        $form->addText('lastname', 'Příjmení')->setRequired(true);
        $form->addText('time', 'Doba, od kdy do kdy je možné volat');
        $form->addSelect('sex', 'Pohlaví')->setRequired(true);
        $sex = [0 => 'Muž', 1 => 'Žena'];
        $form['sex']->setItems($sex);
        $form->addUpload('icon', 'Ikona');
        $form->addTextArea('note', 'Poznámka');
        $form->addSubmit('changeInformation', 'Změnit údaje');

        $userInformations = $this->userManager->get($this->user);
        $form->setDefaults([
            'phone' => $userInformations[Model\UserManager::COLUMN_PHONE],
            'firstname' => $userInformations[Model\UserManager::COLUMN_FIRSTNAME],
            'lastname' => $userInformations[Model\UserManager::COLUMN_LASTNAME],
            'time' => $userInformations[Model\UserManager::COLUMN_TIME],
            'sex' => $userInformations[Model\UserManager::COLUMN_SEX],
            'icon' => $userInformations[Model\UserManager::COLUMN_ICON],
            'note' => $userInformations[Model\UserManager::COLUMN_NOTE]
        ]);

        $form->onSuccess[] = function(Form $form, $values){
            $filename = $this->userManager->get($this->user)[Model\UserManager::COLUMN_ICON];
            if(!empty($values["icon"]->getName())){
                $filename = $values["icon"]->getName();
                $path = __DIR__ . "/../../www/images/icons/" . $filename;
                while(file_exists($path)) {
                    $filename = "0".$filename;
                    $path = __DIR__ . "/../../www/images/icons/" . $filename;
                }
                $values["icon"]->move($path);
            }

            $this->userManager->edit($this->user, [
                Model\UserManager::COLUMN_PHONE => $values["phone"],
                Model\UserManager::COLUMN_FIRSTNAME => $values["firstname"],
                Model\UserManager::COLUMN_LASTNAME => $values["lastname"],
                Model\UserManager::COLUMN_TIME => $values["time"],
                Model\UserManager::COLUMN_SEX => $values["sex"],
                Model\UserManager::COLUMN_ICON => $filename,
                Model\UserManager::COLUMN_NOTE => $values["note"]
            ]);
        };
	    return $form;
    }

	/**
	 * @return Form
	 */
	public function create(callable $onSuccess)
	{
		$form = $this->factory->create();
		$form->addText('username', 'Zvolte uživatelské jméno:')
			->setRequired('Zvolte si prosím uživatelské jméno.');

		$form->addEmail('email', 'Váš e-mail:')
			->setRequired('Vyplňte prosím Váš e-mail.');

		$form->addPassword('password', 'Heslo:')
			->setOption('description', sprintf('minimálně %d znaků', self::PASSWORD_MIN_LENGTH))
			->setRequired('Prosím zvolte si heslo.')
			->addRule($form::MIN_LENGTH, null, self::PASSWORD_MIN_LENGTH);

		$form->addText('phone', 'Telefon');
		$form->addText('firstname', 'Jméno')->setRequired(true);
		$form->addText('lastname', 'Příjmení')->setRequired(true);
		$form->addText('time', 'Doba, od kdy do kdy je možné volat');
		$form->addSelect('sex', 'Pohlaví')->setRequired(true);
		$sex = [0 => 'Muž',1 => 'Žena'];
		$form['sex']->setItems($sex);
		$form->addUpload('icon', 'Ikona');
		$form->addTextArea('note', 'Poznámka');

		$form->addSubmit('register', 'Registrovat se');

		$form->onSuccess[] = function (Form $form, $values) use ($onSuccess) {
			try {
                $filename = null;
                if(!empty($values["icon"]->getName())){
                    $filename = $values["icon"]->getName();
                    $path = __DIR__ . "/../../www/images/icons/" . $filename;
                    while(file_exists($path)) {
                        $filename = "0".$filename;
                        $path = __DIR__ . "/../../www/images/icons/" . $filename;
                    }
                    $values["icon"]->move($path);
                }

			    $array = [Model\UserManager::COLUMN_NAME => $values["username"],
                    Model\UserManager::COLUMN_EMAIL => $values["email"],
                    "heslo" => $values["password"],
                    Model\UserManager::COLUMN_PHONE => $values["phone"],
                    Model\UserManager::COLUMN_FIRSTNAME => $values["firstname"],
                    Model\UserManager::COLUMN_LASTNAME => $values["lastname"],
                    Model\UserManager::COLUMN_TIME => $values["time"],
                    Model\UserManager::COLUMN_SEX => $values["sex"],
                    Model\UserManager::COLUMN_ICON => $filename,
                    Model\UserManager::COLUMN_NOTE => $values["note"]];
				$this->userManager->add($array);
				$this->registrationManager->sendRegisterEmail($values["username"],
                    $values["password"], $values["email"]);
			} catch (Model\DuplicateNameException $e) {
				$form['username']->addError('Uživatelské jméno je již zvolené někým jiným.');
				return;
			}
			$onSuccess();
		};

		return $form;
	}
}
