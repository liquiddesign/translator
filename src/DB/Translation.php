<?php

namespace Translator\DB;

use StORM\Entity;

/**
 * @table{"name":"translator_translation"}
 */
class Translation extends Entity
{
	
	/**
	 * @column{"mutations":true}
	 */
	public ?string $text;
}
