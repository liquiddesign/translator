<?php

declare(strict_types=1);

use Latte\Loaders\StringLoader;

require_once __DIR__ . '/../vendor/autoload.php';

$container = \Translator\Tests\Bootstrap::createContainer();

$translator = $container->getByType(\Translator\Translator::class);

dump($translator);
$translator->setMutation('en');
dump($translator->translate("Košík1"));
dump($translator->getUntranslatedCount());


