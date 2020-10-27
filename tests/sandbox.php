<?php

declare(strict_types=1);

use Latte\Loaders\StringLoader;

require_once __DIR__ . '/../vendor/autoload.php';

$container = \Translator\Tests\Bootstrap::createContainer();

$translator = $container->getByType(\Translator\Translator::class);


dump($translator);
dump($translator->translate("Košík123"));
$translator->setMutation('cz');
dump($translator->translate("kosik"));
$translator->setMutation('en');
dump($translator->translate("kosik"));
dump($translator->getUntranslated());



