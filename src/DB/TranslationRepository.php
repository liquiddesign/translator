<?php

namespace Translator\DB;

use League\Csv\Reader;
use League\Csv\Writer;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Localization\Translator;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use StORM\Collection;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;
use Tracy\Debugger;

/**
 * Class TranslationRepository
 * @extends \StORM\Repository<\Translator\DB\Translation>
 */
class TranslationRepository extends Repository implements Translator
{
	private ?string $activeMutation = null;

	private string $defaultMutation = 'cs';

	private bool $cacheActive = false;

	private bool $createMode = false;

	/**
	 * @var array<string>
	 */
	private array $fallbacks = [];

	/**
	 * @var array<string>
	 */
	private array $currentUntranslated = [];

	/**
	 * @var array<string>
	 */
	private array $scopeLabels = [];

	/**
	 * @var array<array<string>>
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
	 * @return array<string>
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
	 * @return array<string>
	 */
	public function getCurrentUntranslated(): array
	{
		return $this->currentUntranslated;
	}

	public function translate($message, ...$parameters): string
	{
		$parsedMessage = \explode('.', (string) $message);

		if (!isset($parameters[0])) {
			\trigger_error("Label of SCOPE and ID '$message' is not set", \E_USER_WARNING);
		}

		if (\count($parsedMessage) !== 2) {
			\trigger_error(
				"Use exactly one '.' in to separate SCOPE and ID. Message '$message' given.",
				\E_USER_WARNING,
			);
		}

		$defaultMessage = (string) $parameters[0];
		$vars = $parameters[1] ?? [];
		[$scope, $id] = $parsedMessage;

		if ($this->cacheActive) {
			$this->scopeTranslations[$scope] ??= $this->cache->load(
				"$scope.$id." . $this->getMutation(),
				function () use ($scope) {
					return $this->getScopeTranslations($scope);
				},
			);
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

		return \vsprintf(
			$this->scopeTranslations[$scope][$message] ?? $this->getEmptyMessage($id, $defaultMessage),
			$vars,
		);
	}

	public function createTranslationsSnapshot(
		string $backupDir,
		array $mutations = [],
		string $dateTimeFormat = 'Y-m-d_H-i-s'
	): void {
		\Nette\Utils\FileSystem::createDir($backupDir);
		$backupFilename = $backupDir . \DIRECTORY_SEPARATOR . (new \Carbon\Carbon())->format($dateTimeFormat) . '.csv';

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
			$mutation = Strings::substring($value, Strings::indexOf($value, '_') + 1);
			
			if (!\count($availableMutations) || !Arrays::contains($availableMutations, $mutation)) {
				unset($mutationsInFile[$key]);

				continue;
			}

			$mutationsInFile[$key] = $mutation;
		}

		foreach ($reader->getRecords() as $value) {
			$newValue = [
				'uuid' => $value['uuid'],
			];

			if (isset($value['label'])) {
				$newValue['label'] = $value['label'];
			}

			foreach ($mutationsInFile as $mutationV) {
				$newValue['text'][$mutationV] = $value['text_' . $mutationV] ?: null;
			}

			$this->syncOne($newValue);
		}
	}

	public function exportTranslationsCsv(string $tempFilename, array $mutationsToExport = []): void
	{
		$writer = Writer::createFromPath($tempFilename, 'w+');
		$writer->setDelimiter(';');
		$writer->setOutputBOM(Writer::BOM_UTF8);

		$translations = $this->many()->orderBy([$this->getStructure()->getPK()->getName()]);

		$header = [
			'uuid',
			'label',
		];

		foreach ($mutationsToExport as $value) {
			$header[] = "text_$value";
		}

		$writer->insertOne($header);
		
		foreach ($translations as $translation) {
			$row = [
				$translation->getPK(),
				$translation->label,
			];

			foreach ($mutationsToExport as $value) {
				$row[] = $translation->getValue('text', $value);
			}

			$writer->insertOne($row);
		}
	}

	/**
	 * @param string $scope
	 * @return array<string>
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
		return $this->syncOne([
			'uuid' => $scope . '.' . $id,
			'label' => $defaultMessage,
			'text' => [$this->defaultMutation => $defaultMessage],
		], ['label']);
	}

	private function getEmptyMessage(string $id, $defaultMessage): string
	{
		return $this->defaultMutation !== $this->getMutation() ? '░' . $id . '░' : $defaultMessage;
	}
}
