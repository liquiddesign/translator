<?php

namespace Translator\DB;

use League\Csv\Reader;
use League\Csv\Writer;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Localization\Translator;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use StORM\Collection;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;
use Tracy\Debugger;

class TranslationRepository extends Repository implements Translator
{
	private ?string $activeMutation = null;

	private string $defaultMutation = 'cs';

	private bool $cacheActive = false;

	private bool $createMode = false;

	/**
	 * @var string[]
	 */
	private array $fallbacks = [];

	/**
	 * @var string[]
	 */
	private array $currentUntranslated = [];

	/**
	 * @var string[]
	 */
	private array $scopeLabels = [];

	/**
	 * @var string[][]
	 */
	private array $scopeTranslations = [];

	private Cache $cache;

	public function __construct(DIConnection $connection, SchemaManager $schemaManager, IStorage $storage)
	{
		parent::__construct($connection, $schemaManager);

		$this->cache = new Cache($storage, 'translator');
	}

	public function setCache(bool $cacheActive): void
	{
		$this->cacheActive = $cacheActive;
	}

	public function setCreateMode(bool $createMode): void
	{
		$this->createMode = $createMode;
	}

	public function setDefaultMutation(string $defaultMutation): void
	{
		$this->defaultMutation = $defaultMutation;
	}

	public function setMutation(string $mutation): void
	{
		$this->activeMutation = $mutation;
	}

	public function setScopeLabels(array $labels): void
	{
		$this->scopeLabels = $labels;
	}

	public function setFallbacks(array $fallbacks): void
	{
		$this->fallbacks = $fallbacks;
	}

	/**
	 * @return string[]
	 */
	public function getScopeLabels(): array
	{
		return $this->scopeLabels;
	}

	public function getMutation(): string
	{
		return $this->activeMutation ?: $this->connection->getMutation();
	}

	public function getFallback(): ?string
	{
		return $this->fallbacks[$this->getMutation()] ?? null;
	}

	public function getTranslations(?string $scope = null): Collection
	{
		$collection = $this->many($this->getMutation());

		if ($scope !== null) {
			$collection->where('this.uuid LIKE :scope', ['scope' => "$scope.%"]);
		}

		return $collection;
	}

	/**
	 * @return string[]
	 */
	public function getCurrentUntranslated(): array
	{
		return $this->currentUntranslated;
	}

	public function translate($message, ...$parameters): string
	{
		$parsedMessage = \explode('.', (string)$message);

		if (!isset($parameters[0])) {
			\trigger_error("Label of SCOPE and ID '$message' is not set", \E_USER_WARNING);
		}

		if (\count($parsedMessage) !== 2) {
			\trigger_error("Use exactly one '.' in to separate SCOPE and ID. Message '$message' given.",
				\E_USER_WARNING);
		}

		$defaultMessage = (string)$parameters[0];
		$vars = $parameters[1] ?? [];
		[$scope, $id] = $parsedMessage;

		if ($this->cacheActive) {
			$this->scopeTranslations[$scope] ??= $this->cache->load("$scope.$id." . $this->getMutation(),
				function () use ($scope) {
					return $this->getScopeTranslations($scope);
				});
		} else {
			$this->scopeTranslations[$scope] ??= $this->getScopeTranslations($scope);
		}

		if (!isset($this->scopeTranslations[$scope][$message])) {
			if (Debugger::$showBar) {
				$this->currentUntranslated["$scope.$id"] = $defaultMessage;
			}

			if ($this->createMode) {
				$this->saveTranslation($scope, $id, $defaultMessage);
			}
		}

		return \vsprintf($this->scopeTranslations[$scope][$message] ?? $this->getEmptyMessage($id, $defaultMessage),
			$vars);
	}

	/**
	 * @param string $scope
	 * @return string[]
	 */
	private function getScopeTranslations(string $scope): array
	{
		$fallback = $this->getFallback();
		$mutationSuffix = $this->getConnection()->getAvailableMutations()[$this->getMutation()];
		$collection = $this->getTranslations($scope);
		$fallbackSuffix = $fallback ? ($this->getConnection()->getAvailableMutations()[$fallback] ?? null) : null;

		if ($fallbackSuffix) {
			$collection->select(['text' => "IF(text$mutationSuffix IS NULL, text$fallbackSuffix, text$mutationSuffix)"]);
		}

		return $collection->toArrayOf('text');
	}

	private function saveTranslation(string $scope, string $id, string $defaultMessage): Translation
	{
		/** @var \Translator\DB\Translation $translation */
		$translation = $this->syncOne([
			'uuid' => $scope . '.' . $id,
			'label' => $defaultMessage,
			'text' => [$this->defaultMutation => $defaultMessage],
		], ['label']);

		return $translation;
	}

	private function getEmptyMessage(string $id, $defaultMessage): string
	{
		return $this->defaultMutation !== $this->getMutation() ? '░' . $id . '░' : $defaultMessage;
	}

	public function createTranslationsSnapshot(
		string $backupDir,
		array $mutations = [],
		string $dateTimeFormat = 'Y-m-d_H-i-s'
	): void {
		\Nette\Utils\FileSystem::createDir($backupDir);
		$backupFilename = $backupDir . \DIRECTORY_SEPARATOR . (new DateTime())->format($dateTimeFormat) . '.csv';

		$this->exportTranslationsCsv($backupFilename, $mutations);
	}

	public function importTranslationsFromString(string $translationString, array $availableMutations): void
	{
		$this->importTranslationsFromReader(Reader::createFromString($translationString), $availableMutations);
	}

	public function importTranslationsFromFile(string $filename, array $availableMutations): void
	{
		$this->importTranslationsFromReader(Reader::createFromPath($filename), $availableMutations);
	}

	public function importTranslationsFromReader(Reader $reader, array $availableMutations): void
	{
		$reader->setDelimiter(';');
		$reader->setHeaderOffset(0);

		$mutationsInFile = \preg_grep('/^text_(\w+)/', $reader->getHeader());

		foreach ($mutationsInFile as $key => $value) {
			$mutation = \substr($value, \strpos($value, '_', 0) + 1);

			if (!\in_array($mutation, $availableMutations)) {
				unset($mutationsInFile[$key]);
				continue;
			}

			$mutationsInFile[$key] = $mutation;
		}

		foreach ($reader->getRecords() as $key => $value) {
			$newValue = [
				'uuid' => $value['uuid'],
			];

			if (isset($value['label'])) {
				$newValue['label'] = $value['label'];
			}

			foreach ($mutationsInFile as $mutationK => $mutationV) {
				$newValue['text'][$mutationV] = $value['text_' . $mutationV] ?: null;
			}

			$this->syncOne($newValue);
		}
	}

	public function exportTranslationsCsv(string $tempFilename, array $mutationsToExport = [])
	{
		$writer = Writer::createFromPath($tempFilename, 'w+');
		$writer->setDelimiter(';');
		$writer->setOutputBOM(Writer::BOM_UTF8);

		/** @var \Translator\DB\Translation[] $translations */
		$translations = $this->many()->orderBy([$this->getStructure()->getPK()->getName()]);

		$header = [
			'uuid',
			'label',
		];

		foreach ($mutationsToExport as $key => $value) {
			$header[] = "text_$value";
		}

		$writer->insertOne($header);

		foreach ($translations as $translation) {
			$row = [
				$translation->getPK(),
				$translation->label,
			];

			foreach ($mutationsToExport as $key => $value) {
				$row[] = $translation->getValue('text', $value);
			}

			$writer->insertOne($row);
		}
	}
}
