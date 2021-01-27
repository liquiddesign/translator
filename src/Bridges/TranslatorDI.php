<?php

declare(strict_types=1);

namespace Translator\Bridges;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Translator\DB\TranslationRepository;

class TranslatorDI extends \Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'defaultMutation' => Expect::bool('cs'),
			'cache' => Expect::bool(false),
			'createMode' => Expect::bool(false),
			'scopeLabels' => Expect::arrayOf('string'),
			'fallbacks' => Expect::arrayOf('string'),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = (array)$this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$service = $builder->addDefinition($this->prefix('translation'))->setType(TranslationRepository::class);
		
		$service->addSetup('setCache', [$config['cache']]);
		$service->addSetup('setCreateMode', [$config['createMode']]);
		$service->addSetup('setDefaultMutation', [$config['defaultMutation']]);
		$service->addSetup('setScopeLabels', [$config['scopeLabels']]);
		$service->addSetup('setFallbacks', [$config['fallbacks']]);
		
		$service->addSetup('@Tracy\Bar::addPanel', [
			new \Nette\DI\Definitions\Statement(TranslatorTracy::class, []),
		]);
	}
}
