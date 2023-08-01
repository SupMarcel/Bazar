<?php

namespace App\Presenters;

use Nette;
use Contributte;
use Nette\Forms\Form;
use App\Model\UserManager;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{    /** Zpráva typu informace. */
    const MSG_INFO = 'info';
    /** Zpráva typu úspěch. */
    const MSG_SUCCESS = 'success';
    /** Zpráva typy chyba. */
    const MSG_ERROR = 'danger';
    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    /** @var Contributte\Translation\LocalesResolvers\Session @inject */
    public $translatorSessionResolver;
    /** @var UserManager */
    protected $userManager;
    
    public function injectUserManager(UserManager $userManager){
        $this->userManager = $userManager;
    }
    
    public function handleChangeLocale(string $locale): void
    {
        $this->translatorSessionResolver->setLocale($locale);
        setcookie('language', $locale, 0, "/");
        if ($this->getUser()->isLoggedIn()){
            $user = $this->userManager->get($this->getUser()->id);
            if($user->language != $locale){
               $this->userManager->editLanguage($user->id, $locale); 
            }
        }
	$this->redirect('this');
    }
   
      /** Před vykreslováním každé akce u všech presenterů předává společné proměné do celkového layoutu webu. */
    protected function beforeRender()
    {
        parent::beforeRender();
        
        $languages = ['cs'=>'Czech',
                      'en'=>'English'];
        
        if (!isset($_SESSION["__NF"]["DATA"]["Contributte\Translation\LocalesResolvers\Session"]['locale'])) {
            if (isset($_COOKIE['language'])) {
                $locale = $_COOKIE['language'];
                $this->translatorSessionResolver->setLocale($_COOKIE['language']);
            } else {
                $locale = $this->getHttpRequest()->detectLanguage(array_keys($languages));
                $this->translatorSessionResolver->setLocale($locale);
                setcookie('language', $locale, 0, "/");
            }
        } else {
            $locale = $_SESSION["__NF"]["DATA"]["Contributte\Translation\LocalesResolvers\Session"]['locale'];
        }
        if ($this->getUser()->isLoggedIn()){
            $seller = $this->userManager->get($this->getUser()->id);
            $this->template->seller = $seller;
            if($seller->language != $locale){
               $this->userManager->editLanguage($seller->id, $locale); 
            }
        }
        
        $this->template->setTranslator($this->translator);       
        $this->template->actualLanguageCode = $locale;
        $this->template->languages = $languages;
        foreach ($languages as $key => $value){
           if ($key != $locale) {
               continue;
           }
            $this->template->actualLanquage = $this->translator->translate($value);
            $this->template->actualLanguageKey = $key;
        }
        $this->template->admin = $this->getUser()->isInRole('admin');
        $this->template->domain = $this->getHttpRequest()->getUrl()->getHost();      // Předá jméno domény do šablony.
        $this->template->formPath = __DIR__ . '/templates/form.latte'; // Předá cestu ke globální šabloně formulářů do šablony.
       
    }  
    
}
