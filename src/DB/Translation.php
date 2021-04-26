<?php

namespace Translator\DB;

use StORM\Entity;

/**
 * @table{"name":"translator_translation"}
 */
class Translation extends Entity
{
	/**
	 * @column{"type":"varchar","length":64}
	 * @pk
	 */
	public string $uuid;
	
	/**
	 * @column{"type":"longtext"}
	 */
	public string $label;
	
	/**
	 * @column{"mutations":true,"type":"longtext"}
	 */
	public ?string $text;
}
