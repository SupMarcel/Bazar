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
    
    const LANGUAGES = ['cs-cz'=>'Czech',
                      'en'=>'English'];
    
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
    
    protected function languageCode() {
       if (!isset($_SESSION["__NF"]["DATA"]["Contributte\Translation\LocalesResolvers\Session"]['locale'])) {
            if (isset($_COOKIE['language'])) {
                $locale = $_COOKIE['language'];
                $this->translatorSessionResolver->setLocale($_COOKIE['language']);
            } else {
                $locale = $this->getHttpRequest()->detectLanguage(array_keys(self::LANGUAGES));
                $this->translatorSessionResolver->setLocale($locale);
                setcookie('language', $locale, 0, "/");
            }
        } else {
            $locale = $_SESSION["__NF"]["DATA"]["Contributte\Translation\LocalesResolvers\Session"]['locale'];
        }
        return $locale;
    }
    
    protected function getSeller() {
        if ($this->getUser()->isLoggedIn()){
            $seller = $this->userManager->get($this->getUser()->id);
        }else {
            $seller = null;
        } 
        return $seller;
    }
    
    
    private function languagesValue() {
        $languesValue = null; 
        foreach (self::LANGUAGES as $key => $value){
           if ($key != $this->languageCode()) {
               continue;
           }
          $languesValue= $this->translator->translate($value);
        }  
        return $languesValue;
    }  
    
    private function languagesKey() {
        $languesKey = null;
        foreach (self::LANGUAGES as $key => $value){
           if ($key != $this->languageCode()) {
               continue;
           }
          $languesKey = $key;
        }  
        return $languesKey;
    }
    
      /** Před vykreslováním každé akce u všech presenterů předává společné proměné do celkového layoutu webu. */
    protected function beforeRender()
    {
        parent::beforeRender();
       
        if (!empty($this->getSeller())){
            $this->template->seller = $this->getSeller();
            if($this->getSeller()->language != $this->languageCode()){
               $this->userManager->editLanguage($this->getSeller()->id, $this->languageCode()); 
            }
        }
        
        $this->template->setTranslator($this->translator);       
        $this->template->actualLanguageCode = $this->languageCode();
        $this->template->languages = self::LANGUAGES;
        $this->template->actualLanquage = $this->languagesValue();
        $this->template->actualLanguageKey = $this->languagesKey();
        $this->template->admin = $this->getUser()->isInRole('admin');
        $this->template->domain = $this->getHttpRequest()->getUrl()->getHost();      // Předá jméno domény do šablony.
        $this->template->formPath = __DIR__ . '/templates/form.latte'; // Předá cestu ke globální šabloně formulářů do šablony.
       
    }  
    
}
