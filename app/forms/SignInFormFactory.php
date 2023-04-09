<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\User;
use App\Model\UserManager;


class SignInFormFactory extends FormFactory
{
	use Nette\SmartObject;

	/** @var User */
	private $user;


	public function __construct(User $user)
	{	
		$this->user = $user;
	}


	/**
	 * @return Form
	 */
	public function createSignForm(callable $onSuccess)
	{
		$form = $this->create();
		$form->addText('username', $this->translator->translate ('messages.'.UserManager::TABLE_NAME.'.'.UserManager::COLUMN_NAME).':')
			->setRequired('Please enter your username.');

		$form->addPassword('password',  $this->translator->translate ('messages.'.UserManager::TABLE_NAME.'.'.UserManager::COLUMN_PASSWORD_HASH).':')
			->setRequired($this->translator->translate('messages.signInForm.enter_password'));

		$form->addCheckbox('remember', $this->translator->translate('messages.signInForm.Remember_me'));

		$form->addSubmit('send', $this->translator->translate('messages.signInForm.Sign_in'));

		$form->onSuccess[] = function (Form $form, $values) use ($onSuccess) {
			try {
				//$this->user->setExpiration($values->remember ? '14 days' : '14 days', true);
				$this->user->login($values->username, $values->password);
			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError($this->translator->translate('messages.signInForm.username_incorrect'));
				return;
			}
			$onSuccess();
		};

		return $form;
	}
}
