<?php

// app/Control/SubcategoriesControl.php

namespace App\Control;

use Nette\Application\UI\Control;
use App\Services\FilterService;

class SubcategoriesControl extends Control
{
    const TEMPLATE = __DIR__ . '/templates/subcategories.latte';

    /** @var FilterService */
    private $filterService;
    
    protected $paramsa;

    public function __construct(FilterService $filterService, array $paramsa = null)
    {
        $this->filterService = $filterService;
        $this->paramsa = $paramsa;
    }
    
    public function handleCategoryChange($categoryId) {
        $this->presenter->redirect('Homepage:default', ['category' => $categoryId]);
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(self::TEMPLATE);
        $template->categories = $this->filterService->getActiveSubcategories($this->paramsa);
        $template->render();
    }
}
