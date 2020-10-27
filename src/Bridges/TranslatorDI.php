<?php

declare(strict_types=1);

namespace Translator\Bridges;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Translator\DB\TranslationRepository;
use Translator\Translator;

class TranslatorDI extends \Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'cache' => Expect::bool(true),
			'createMode' => Expect::bool(false),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = (array)$this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$builder->addDefinition($this->prefix('db'))->setType(TranslationRepository::class);
		
		$pages = $builder->addDefinition($this->prefix('main'))->setType(Translator::class);
		
		$pages->addSetup('setCache', [$config['cache']]);
		$pages->addSetup('setCreateMode', [$config['createMode']]);
	}
}
