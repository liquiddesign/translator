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
	
	public function createNew(string $uuid): Entity
	{
		return $this->createOne(['uuid'=>$uuid]);
	}
}
