<?php

namespace App\Presenters;

use Nette;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    
      /** Před vykreslováním každé akce u všech presenterů předává společné proměné do celkového layoutu webu. */
    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->admin = $this->getUser()->isInRole('admin');
        $this->template->domain = $this->getHttpRequest()->getUrl()->getHost();      // Předá jméno domény do šablony.
        $this->template->formPath = __DIR__ . '/../templates/forms/form.latte'; // Předá cestu ke globální šabloně formulářů do šablony.
       
    }  
    
}
