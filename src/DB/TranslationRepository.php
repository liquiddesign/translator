<?php

namespace Translator\DB;

use StORM\DIConnection;
use StORM\Entity;
use StORM\Repository;
use StORM\SchemaManager;

class TranslationRepository extends Repository
{
	
	public function __construct(DIConnection $connection, SchemaManager $schemaManager)
	{
		parent::__construct($connection, $schemaManager);
	}
	
	public function getLang(string $message, $lang): ?Entity
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
	
	public function createNew(string $text, string $lang, array $languages): Entity
	{
		$values = array();
		$values[$lang]=$text;

		foreach (\array_keys($languages) as $key) {
			if ($key === $lang) {
				continue;
			}

			$values[$key] = '';
		}

		return $this->createOne(['text' => $values]);
	}
}
