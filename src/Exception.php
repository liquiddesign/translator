<?php
declare(strict_types=1);

namespace Translator;

class NotAvailableMutation extends \Exception
{
	/**
	 * @var string
	 */
	protected $message = 'This mutation is not available!';
}
