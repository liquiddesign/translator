<?php

declare(strict_types=1);

namespace Translator;

/**
 * Volitelný logger použití překladových klíčů (detekce nepoužívaných klíčů).
 *
 * TranslationRepository::translate() volá record() pro každý požadovaný klíč, je-li logger nastaven
 * přes setUsageLogger(). Implementace musí být levná (hot path) a fail-safe — typicky in-memory buffer
 * s flushem na konci requestu. Volání je obaleno try/catch, takže výjimka logger nikdy nerozbije překlad.
 */
interface TranslationUsageLogger
{
	public function record(string $code): void;
}
