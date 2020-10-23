<?php

declare(strict_types=1);

namespace Translator;

use Nette\Caching\Storages\FileStorage;
use Nette\Localization\ITranslator;
use Translator\DB\TranslationRepository;

class Translator implements ITranslator
{
	private ?string $selectedLanguage = null;
	
	/**
	 * @var mixed[]
	 */
	private array $availableLanguages;
	
	private string $defaultLanguage;
	
	private bool $cacheActive;
	
	private bool $createMode;
	
	private TranslationRepository $translationRepo;
	
	private int $untranslatedCount = 0;
	
	private \Nette\Caching\Cache $cache;
	
	/**
	 * @var mixed[]
	 */
	private array $untranslated = [];
	
	public function __construct(TranslationRepository $translationRepo)
	{
		$this->translationRepo = $translationRepo;
		$storage = new FileStorage(__DIR__.DIRECTORY_SEPARATOR.'/temp');
		$this->cache = new \Nette\Caching\Cache($storage, "translator");
	}
	
	public function setAvailableLanguages(array $availableLanguages): void
	{
		$this->availableLanguages = $availableLanguages;
		
		try {
			$this->setDefaultLanguage(\array_keys($availableLanguages)[0]);
		} catch (NotAvailableLanguage $ignored) {
		}
	}
	
	/**
	 * @param string $defaultLanguage
	 * @throws \Translator\NotAvailableLanguage
	 */
	public function setDefaultLanguage(string $defaultLanguage): void
	{
		if (!isset($this->getAvailableLanguages()[$defaultLanguage])) {
			throw new NotAvailableLanguage();
		}
		
		$this->defaultLanguage = $defaultLanguage;
		$this->setLanguage($defaultLanguage);
	}
	
	/**
	 * @return mixed[]
	 */
	public function getAvailableLanguages(): array
	{
		return $this->availableLanguages;
	}
	
	public function setCache(bool $cacheActive): void
	{
		$this->cacheActive = $cacheActive;
	}
	
	public function setCreateMode(bool $createMode): void
	{
		$this->createMode = $createMode;
	}
	
	public function getLanguage(): string
	{
		return $this->selectedLanguage ?: $this->getDefaultLanguage();
	}
	
	/**
	 * @param string $selectedLanguage
	 * @throws \Translator\NotAvailableLanguage
	 */
	public function setLanguage(string $selectedLanguage): void
	{
		if (!isset($this->getAvailableLanguages()[$selectedLanguage])) {
			throw new NotAvailableLanguage();
		}
		
		$this->selectedLanguage = $selectedLanguage;
	}
	
	public function getDefaultLanguage(): string
	{
		return $this->defaultLanguage;
	}
	
	/**
	 * @return mixed[]
	 */
	public function getUntranslated(): array
	{
		return $this->untranslated;
	}
	
	public function getUntranslatedCount(): int
	{
		return $this->untranslatedCount;
	}
	
	public function addUntranslatedString(string $string): void
	{
		if (!isset($this->untranslated[$string])) {
			$this->untranslatedCount++;
			$this->untranslated[$string] = true;
		}
	}
	
	public function checkLanguageAvailable($lang): bool
	{
		return isset($this->getAvailableLanguages()[$lang]);
	}
	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function translate($message, ...$parameters): string
	{
		$cache = $this->cache;
		$arguments = \func_get_args();
		
		$lang = isset($arguments['lang']) && $this->checkLanguageAvailable($arguments['lang']) ? $arguments['lang'] : $this->getLanguage();
		
		if ($this->cacheActive === true) {
			$translation = $cache->load($message . '_' . $this->getDefaultLanguage(), function () use ($lang, $message) {
				return $this->getTranslation($message, $lang);
			});
		} else {
			$translation = $this->getTranslation($message, $lang);
		}
		
		if ($translation === '') {
			$translation = $message;
			$this->addUntranslatedString($message);
		}
		
		return \vsprintf($translation, $arguments);
	}
	
	/**
	 * @param string $message
	 * @param string $lang
	 * @return string
	 */
	private function getTranslation(string $message, string $lang): string
	{
		$translation = $this->translationRepo->getLang($message, $this->getDefaultLanguage());
		
		if ($translation === null) {
			if ($this->createMode === true) {
				$this->translationRepo->createNew($message, $this->getDefaultLanguage(), $this->getAvailableLanguages());
			}
			
			$this->addUntranslatedString($message);
			
			return $message;
		}
		
		return $translation->getValue('text', $lang);
	}
}
