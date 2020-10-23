<?php

declare(strict_types=1);

use Latte\Loaders\StringLoader;

require_once __DIR__ . '/../vendor/autoload.php';

$container = \Translator\Tests\Bootstrap::createContainer();

$translator = $container->getByType(\Translator\Translator::class);

dump($translator);
$translator->setLanguage('en');
dump($translator->translate("Košík"));
dump($translator->getUntranslatedCount());
//$translator->setLanguage('cz');
//dump($translator->translate("Basket"));

//dump($translator->translate("Basket"));


