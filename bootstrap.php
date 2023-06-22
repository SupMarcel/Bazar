<?php
// bootstrap.php

require_once __DIR__ . '/vendor/autoload.php'; // Načtení Composer autoloaderu

// Importy pro testování s Nette Testerem
use Tester\Environment;

// Inicializace prostředí pro Nette Tester
Environment::setup();

// Zde můžete přidat další konfigurace nebo importy, které potřebujete pro spuštění testů

