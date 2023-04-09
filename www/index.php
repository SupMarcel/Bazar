<?php

 // $container = require __DIR__ . '/../app/bootstrap.php';

// $container->getByType(Nette\Application\Application::class)
//	->run();

declare(strict_types=1);


require __DIR__ . '/../vendor/autoload.php';

$configurator = App\Bootstrap::boot();
$container = $configurator->createContainer();
$application = $container->getByType(Nette\Application\Application::class);
$application->run();
