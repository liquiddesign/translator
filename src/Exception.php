<?php
declare(strict_types=1);

namespace Translator;

class NotAvailableLanguage extends \Exception
{
	protected $message = 'This language is not available!';
}

class TranslationNotFound extends \Exception
{
}
