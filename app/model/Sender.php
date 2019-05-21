<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.9.2018
 * Time: 13:53
 */

namespace App\Model;

use Nette;
use Latte\Engine;

class Sender
{
    /** @var Nette\Application\LinkGenerator */
    private $linkGenerator;
    /** @var Nette\Application\UI\ITemplateFactory */
    private $templateFactory;

    public function __construct(Nette\Application\LinkGenerator $linkGenerator,
                                Nette\Application\UI\ITemplateFactory $templateFactory)
    {
        $this->linkGenerator = $linkGenerator;
        $this->templateFactory = $templateFactory;
    }

    public function createTemplate(){
        $template = $this->templateFactory->createTemplate();
        $template->getLatte()->addProvider('uiControl', $this->linkGenerator);

        return $template;
    }


}