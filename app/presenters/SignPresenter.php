<?php

namespace App\Presenters;

use App\Forms;
use App\Model\UserManager;
use Nette\Application\UI\Form;


class SignPresenter extends BasePresenter
{
	/** @persistent */
	public $backlink = '';

	/** @var Forms\SignInFormFactory */
	private $signInFactory;

	/** @var Forms\SignUpFormFactory */
	private $signUpFactory;

	/** @var  UserManager */
	private $userManager;


	public function __construct(Forms\SignInFormFactory $signInFactory, Forms\SignUpFormFactory $signUpFactory,
    UserManager $userManager)
	{
		$this->signInFactory = $signInFactory;
		$this->signUpFactory = $signUpFactory;
		$this->userManager = $userManager;
	}


	/**
	 * Sign-in form factory.
	 * @return Form
	 */
	protected function createComponentSignInForm()
	{
		return $this->signInFactory->create(function () {
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		});
	}

    public function createComponentEditForm(){
        $form =  $this->signUpFactory->createEditForm($this->getUser()->id);
        $form->onSuccess[] = function(Form $form, $values){
            $this->redirect("Homepage:");
        };
        return $form;
    }

	/**
	 * Sign-up form factory.
	 * @return Form
	 */
	protected function createComponentSignUpForm()
	{
		return $this->signUpFactory->create(function () {
			$this->redirect('Homepage:');
		});
	}

    public function renderIn(){
        $this->template->loggedIn = $this->getUser()->id !== null;
    }

    public function renderOut(){
        $this->template->loggedIn = $this->getUser()->id !== null;
    }

    public function renderUp(){
        $this->template->loggedIn = $this->getUser()->id !== null;
    }

	public function renderUpdate(){
        $this->template->loggedIn = $this->getUser()->id !== null;
        if($this->getUser()->id !== null){
            $identity = $this->getUser()->id;
            $user = $this->userManager->get($identity);
            $username = $user[UserManager::COLUMN_NAME];
            $icon = $user[UserManager::COLUMN_ICON];
            $this->template->username = $username;
            $this->template->icon = $icon;
            $sex = $user[UserManager::COLUMN_SEX];
            $this->template->sex = $sex;
        }
    }


	public function actionOut()
	{
		$this->getUser()->logout();
        $this->session->destroy();
	}

}
