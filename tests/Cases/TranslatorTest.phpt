<?php
declare(strict_types=1);

namespace Translator\Tests\Cases;

use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;
use Translator\NotAvailableLanguage;
use Translator\Tests\Bootstrap;
use Translator\Translator;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class TranslatorTest
 * @package Tests
 * @testCase
 */
class TranslatorTest extends TestCase
{
	private Translator $translator;
	
	private Container $container;
	
	protected function setUp()
	{
		$this->container = Bootstrap::createContainer();
		
		$this->translator = $this->container->getByType(Translator::class);
	}
	
	public function testExists()
	{
		Assert::notNull($this->translator);
	}
	
	public function testLanguageChanging()
	{
		Assert::equal('Košík', $this->translator->translate('Košík'));
		$this->translator->setLanguage("cz");
		Assert::equal('Košík', $this->translator->translate('Košík'));
		$this->translator->setLanguage("en");
		Assert::equal('Basket', $this->translator->translate('Košík'));
		$this->translator->setLanguage("cz");
		Assert::equal('Košík', $this->translator->translate('Košík'));
		$this->translator->setLanguage("en");
		Assert::notEqual('Košík', $this->translator->translate('Košík'));
	}
	
	public function testNotExistTranslation()
	{
		$this->translator->setLanguage("en");
		Assert::equal('Košík123', $this->translator->translate('Košík123'));
		Assert::equal('Košík123', $this->translator->translate('Košík123'));
		$this->translator->setLanguage("cz");
		Assert::equal('Košík123', $this->translator->translate('Košík123'));
	}
	
}

(new TranslatorTest())->run();