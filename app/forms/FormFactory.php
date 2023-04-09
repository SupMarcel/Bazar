<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;

class FormFactory
{ 
         /** Zpráva typu informace. */
        const MSG_INFO = 'info';
        /** Zpráva typu úspěch. */
        const MSG_SUCCESS = 'success';
        /** Zpráva typy chyba. */
        const MSG_ERROR = 'danger';
  	use Nette\SmartObject;
        
        protected $translator;
        
        public function injectTranslator(ITranslator $translator){
            $this->translator = $translator;
        }
        
	/**
	 * @return Form
	 */
	public function create()
	{
                 
            Nette\Forms\Validator::$messages[Form::FILLED] = $this->translator->translate("messages.FormFactory.fill_field");
            $form = new Form;
            
            $renderer = $form->getRenderer();
            $renderer->wrappers['controls']['container'] = 'div';
            $renderer->wrappers['pair']['container'] = 'div';
            $renderer->wrappers['pair']['.optional'] = 'red';
            $renderer->wrappers['label']['container'] = 'div';
            $renderer->wrappers['control']['container'] = 'div';
            $renderer->wrappers['control']['.error'] = 'error'; 
            $form->onValidate[] = [$this, 'formError'];
            return $form;
	}
        
        public function formError(Form $form)
        {
        $presenter = $form->getPresenterIfExists();
        if ($presenter) foreach ($form->getErrors() as $error)
            $presenter->flashMessage($error, self::MSG_ERROR);
    }
    }
