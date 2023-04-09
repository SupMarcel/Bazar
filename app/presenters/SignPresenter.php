<?php

namespace App\Presenters;

use App\Forms;
use App\Model\UserManager;
use Nette\Application\UI\Form;
use App\Components\PlanControl;
use Nette\Utils\ArrayHash;
use App\Model\AddressManager;


class SignPresenter extends BasePresenter
{
	/** @persistent */
	public $backlink = '';

	/** @var Forms\SignInFormFactory */
	private $signInFactory;

	/** @var Forms\SignUpFormFactory */
	private $signUpFactory;
        
        /** @var  AddressManager */
	private $addressManager;


	public function __construct(Forms\SignInFormFactory $signInFactory, Forms\SignUpFormFactory $signUpFactory,
                                    AddressManager $addressManager )
	{
		$this->signInFactory = $signInFactory;
		$this->signUpFactory = $signUpFactory;
                $this->addressManager = $addressManager;
	}


	/**
	 * Sign-in form factory.
	 * @return Form
	 */
	protected function createComponentSignInForm()
	{
            return $this->signInFactory->createSignForm(function () {
                  $this->restoreRequest($this->backlink);
                  $this->redirect("Homepage:");
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
		return $this->signUpFactory->createRegistrationForm(function () {
                        $this->flashMessage('Děkujeme za Vaši registraci', self::MSG_SUCCESS);
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
        $this->template->username = "";
        
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
    
    public function handleCountAddresses() {
           if ($this->isAjax()){
               $userId = $this->getUser()->id;
               $countAddresses = $this->addressManager->countAddresses($userId);
               $this->sendJson(['countAddresses'=>$countAddresses]);
           }
    }
    
    public function handleSetActiveAddress() {
           if ($this->isAjax()){
               $userId = $this->getUser()->id;
               $activeAddressId = $this->getParameter("addressId");
               
               if(!empty($activeAddressId)){
                  $this->userManager->editActiveAddress($userId, $activeAddressId);
               }   
               $this->redirect('this');
           }
    }
   


	public function actionOut()
	{
		$this->getUser()->logout();
        $this->session->destroy();
	}
     protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->formPathPlan = __DIR__ . '/templates/formPlan.latte'; // Předá cestu ke globální šabloně formulářů do šablony.
       
    }
}
