<?php

declare(strict_types=1);

namespace Translator;

use Nette\Caching\IStorage;
use Nette\Caching\Storages\FileStorage;
use Nette\Localization\ITranslator;
use Translator\DB\TranslationRepository;

class Translator implements ITranslator
{
	private ?string $selectedMutation = null;
	
	/**
	 * @var mixed[]
	 */
	private array $availableMutations;
	
	private string $defaultMutation;
	
	private bool $cacheActive;
	
	private bool $createMode;
	
	private TranslationRepository $translationRepo;
	
	private int $untranslatedCount = 0;
	
	private \Nette\Caching\Cache $cache;
	
	/**
	 * @var mixed[]
	 */
	private array $untranslated = [];
	
	public function __construct(TranslationRepository $translationRepo, IStorage $storage)
	{
		$this->translationRepo = $translationRepo;
		$this->cache = new \Nette\Caching\Cache($storage, "translator");
	}
	
	public function setAvailableMutations(array $availableMutations): void
	{
		$this->availableMutations = $availableMutations;
		if (count($availableMutations) == 0) {
			return;
		}
		try {
			$this->setDefaultMutation(\array_keys($availableMutations)[0]);
		} catch (NotAvailableMutation $ignored) {
		}
	}
	
	/**
	 * @param string $defaultMutation
	 * @throws \Translator\NotAvailableMutation
	 */
	public function setDefaultMutation(string $defaultMutation): void
	{
		if (!isset($this->getAvailableMutations()[$defaultMutation])) {
			throw new NotAvailableMutation();
		}
		
		$this->defaultMutation = $defaultMutation;
		$this->setMutation($defaultMutation);
	}
	
	/**
	 * @return mixed[]
	 */
	public function getAvailableMutations(): array
	{
		return $this->availableMutations;
	}
	
	public function setCache(bool $cacheActive): void
	{
		$this->cacheActive = $cacheActive;
	}
	
	public function setCreateMode(bool $createMode): void
	{
		$this->createMode = $createMode;
	}
	
	public function getMutation(): string
	{
		return $this->selectedMutation ?: $this->getDefaultMutation();
	}
	
	/**
	 * @param string $selectedMutation
	 * @throws \Translator\NotAvailableMutation
	 */
	public function setMutation(string $selectedMutation): void
	{
		if (!isset($this->getAvailableMutations()[$selectedMutation])) {
			throw new NotAvailableMutation();
		}
		
		$this->selectedMutation = $selectedMutation;
	}
	
	public function getDefaultMutation(): string
	{
		return $this->defaultMutation;
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
	
	public function checkMutationAvailable($mutation): bool
	{
		return isset($this->getAvailableMutations()[$mutation]);
	}
	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function translate($message, ...$parameters): string
	{
		$cache = $this->cache;
		$arguments = \func_get_args();
		
		$mutation = isset($arguments['mutation']) && $this->checkMutationAvailable($arguments['mutation']) ? $arguments['mutation'] : $this->getMutation();
		
		if ($this->cacheActive === true) {
			$translation = $cache->load($message . '_' . $this->getDefaultMutation(), function () use ($mutation, $message) {
				return $this->getTranslation($message, $mutation);
			});
		} else {
			$translation = $this->getTranslation($message, $mutation);
		}
		
		if ($translation == null) {
			$translation = $message;
			$this->addUntranslatedString($message);
		}
		
		return \vsprintf($translation, $arguments);
	}
	
	/**
	 * @param string $message
	 * @param string $mutation
	 * @return string
	 */
	private function getTranslation(string $message, string $mutation): string
	{
		$translation = $this->translationRepo->getStringInMutation($message, $this->getDefaultMutation());
		
		if ($translation === null) {
			if ($this->createMode) {
				$this->translationRepo->createNew($message, $this->getDefaultMutation(), $this->getAvailableMutations());
			}
			
			$this->addUntranslatedString($message);
			
			return $message;
		}
		
		return $translation->getValue('text', $mutation);
	}
}
