<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.9.2018
 * Time: 13:53
 */

namespace App\Model;

use Nette;

trait SenderTrait
{
    /** @var Nette\Application\LinkGenerator */
    protected $linkGenerator;

    /** @var Nette\Application\UI\ITemplateFactory */
    protected $templateFactory;

    public function __construct(Nette\Application\LinkGenerator $linkGenerator,
    Nette\Bridges\ApplicationLatte\TemplateFactory $templateFactory)
    {
        $this->linkGenerator = $linkGenerator;
        $this->templateFactory = $templateFactory;
    }

    protected function createTemplate(){
        $template = $this->templateFactory->createTemplate();
        $template->getLatte()->addProvider('uiControl', $this->linkGenerator);
        return $template;
    }

}