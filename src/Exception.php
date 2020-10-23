<?php
declare(strict_types=1);

namespace Translator;

class NotAvailableMutation extends \Exception
{
	protected $message = 'This mutation is not available!';
}


