<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

/**
 * Továrna na routovací pravidla.
 * Řídí směrování a generovaní URL adres v celé aplikaci.
 * @package App
 */
final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
		$router->addRoute('[<locale=cs-cz cs-cz|en>/]<presenter>/<action>', 'Homepage:default');
		return $router;
	}
}
