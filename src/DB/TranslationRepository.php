<?php

namespace Translator\DB;

use StORM\DIConnection;
use StORM\Entity;
use StORM\Repository;
use StORM\SchemaManager;

class TranslationRepository extends Repository
{
	
	public function __construct(DIConnection $connection = null, SchemaManager $schemaManager = null)
	{
		parent::__construct($connection, $schemaManager);
	}
	
	public function getStringInMutation(string $message, $lang): ?Entity
	{
		try {
			return $this->many()->where('text_'.$lang, $message)->fetch();
		} catch (\Exception $e) {
			return null;
		}
	}
	
	/**
	 * @return array
	 */
	public function getAll(): array
	{
		return $this->many()->toArray();
	}
	
	public function createNew(string $text, string $mutation, array $availableMutations): Entity
	{
		return $this->createOne(['text' => [$mutation => $text]]);
	}
}
