<?php

declare(strict_types=1);

namespace Translator;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Localization\ITranslator;
use Tracy;
use Translator\Bridges\TranslatorTracy;
use Translator\DB\TranslationRepository;

class Translator implements ITranslator
{
	private string $selectedMutation;
	
	private bool $cacheActive = false;
	
	private bool $createMode = false;
	
	private TranslationRepository $translationRepo;
	
	private Cache $cache;
	
	/**
	 * @var mixed[]
	 */
	private array $untranslated = [];
	
	public function __construct(TranslationRepository $translationRepo, IStorage $storage)
	{
		$this->translationRepo = $translationRepo;
		$this->cache = new Cache($storage, 'translator');
		$this->selectedMutation = $translationRepo->getConnection()->getMutation();
		
		Tracy\Debugger::getBar()->addPanel(new TranslatorTracy($this));
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
		return $this->selectedMutation;
	}
	
	/**
	 * @param string $selectedMutation
	 * @throws \Translator\NotAvailableMutation
	 */
	public function setMutation(string $selectedMutation): void
	{
		if (!$this->checkMutationAvailable($selectedMutation)) {
			throw new \InvalidArgumentException("Mutation $selectedMutation is not available");
		}
		
		$this->selectedMutation = $selectedMutation;
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
		return \count($this->untranslated);
	}
	
	public function addUntranslatedString(string $string): void
	{
		if (isset($this->untranslated[$string])) {
			$this->untranslated[$string]++;
		} else {
			$this->untranslated[$string] = 1;
		}
	}
	
	/**
	 * @return string[]
	 */
	public function getAvailableMutations(): array
	{
		return $this->translationRepo->getConnection()->getAvailableMutations();
	}
	
	public function checkMutationAvailable($mutation): bool
	{
		return isset($this->getAvailableMutations()[$mutation]);
	}
	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function translate($uuid, ...$parameters): string
	{
		if (!isset($uuid))
			return '';
		
		$cache = $this->cache;
		$arguments = \func_get_args();
		
		$mutation = isset($arguments['mutation']) && $this->checkMutationAvailable($arguments['mutation']) ? $arguments['mutation'] : $this->getMutation();
		
		if ($this->cacheActive) {
			$translation = $cache->load("$uuid-$mutation", function () use ($mutation, $uuid) {
				return $this->getTranslation($uuid, $mutation);
			});
		} else {
			$translation = $this->getTranslation($uuid, $mutation);
		}
		
		if ($translation === null) {
			$translation = $uuid;
			$this->addUntranslatedString($uuid);
		}
		
		return \vsprintf($translation, $arguments);
	}
	
	private function getTranslation(string $uuid, string $mutation): ?string
	{
		$translation = $this->translationRepo->one($uuid, false, null, $mutation);
		
		if ($translation === null) {
			if ($this->createMode) {
				$this->translationRepo->createNew($uuid);
			}
			
			return null;
		}
		
		return $translation->getValue('text', $mutation);
	}
}
