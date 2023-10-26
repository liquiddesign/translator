<?php

namespace Translator\DB;

use Base\Entity\ShopEntity;

/**
 * @table{"name":"translator_translation"}
 * @index{"name":"translation_unique_code","unique":true,"columns":["code","fk_shop"]}
 */
class Translation extends ShopEntity
{
	/**
	 * @column{"type":"varchar","length":64}
	 * @pk
	 */
	public string $uuid;

	/**
	 * @column
	 */
	public string $code;
	
	/**
	 * @column{"type":"longtext"}
	 */
	public string $label;
	
	/**
	 * @column{"mutations":true,"type":"longtext"}
	 */
	public ?string $text;
}
