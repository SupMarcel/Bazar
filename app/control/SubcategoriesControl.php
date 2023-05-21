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

    public function __construct(FilterService $filterService)
    {
        $this->filterService = $filterService;
    }
    
    public function handleCategoryChange($categoryId) {
        $this->presenter->redirect('Homepage:default', ['category' => $categoryId]);
    }

    public function render(array $params = null)
    {
        $template = $this->template;
        $template->setFile(self::TEMPLATE);
        $template->categories = $this->filterService->getActiveSubcategories();
        $template->render();
    }
}
