<?php
require '../../../vendor/autoload.php';

use App\Model\UserManager;
use Tester\Assert;
use Nette\Database\Explorer;
use Nette\Database\Connection;
use Nette\Database\Structure;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Security\Passwords;
use Contributte\Translation\Translator;
use Tracy\ILogger;


Tester\Environment::lock('mockery', __DIR__ . '/temp');
register_shutdown_function(function () {
    Mockery::close();
});



// předpokládám, že máte třídu Logger implementující ILogger
$logger = new class implements ILogger {
    public function log($value, $priority = self::INFO) {
        // implementace metody log
    }
};

// vytvoříme instanci Connection
$connection = new Connection('mysql:host=localhost;dbname=f61861', 'daniel', 'Bubovice258,');

// vytvoříme instanci Structure
$structure = new Structure($connection, new DevNullStorage());

// vytvoříme instanci Explorer
$database = new Explorer($connection, $structure);

// vytvoříme mock objekt pro třídu Translator
$translator = Mockery::mock(Translator::class);

// nastavíme, jak se má mock objekt chovat
// v tomto případě říkáme, že když je zavolána metoda translate, vrátí se řetězec 'translated text'
$translator->shouldReceive('translate')->andReturn('translated text');

$passwords = new Passwords();

$userManager = new UserManager($database, $logger, $passwords, $translator);

// vytvoříme testovací data
$testData = [
    'user_name' => 'TestUser',
    'password' => 'TestPassword',
    'email' => 'test@example.com',
    'phone' => '603165921',
    'firstname' => 'Marcel',
    'lastname' => 'Sup',
    'opening_hours' => '9-12',
    'note' => 'zkouška',
    'role' => 'žádná',
    'genders' => 1 ,
    'icon' => null,
    'email_subscription' => 1 
    // další pole podle vašich potřeb...
];

// zavoláme metodu add a uložíme vrácené ID
$userId = $userManager->add($testData);

// použijeme assert funkci k ověření, že ID je platné (není prázdné)
Assert::true($userId !== null && $userId > 0);

// zde byste mohl přidat další testy, například zda jsou data správně uložena v databázi
