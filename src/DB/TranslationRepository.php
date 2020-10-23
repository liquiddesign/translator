<?php

namespace Translator\DB;

use StORM\DIConnection;
use StORM\Entity;
use StORM\Repository;
use StORM\SchemaManager;

class TranslationRepository extends Repository
{
	public function __construct(?DIConnection $connection = null, ?SchemaManager $schemaManager = null)
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
	
	public function createNew(string $text, string $mutation): Entity
	{
		return $this->createOne(['text' => [$mutation => $text]]);
	}
}
