<?php

namespace Translator\Bridges;

use Tracy\IBarPanel;
use Translator\DB\TranslationRepository;

class TranslatorTracy implements IBarPanel
{
	public function __construct(protected readonly TranslationRepository $translator)
	{
	}
	
	/**
	 * Renders HTML code for storm panel
	 * @throws \Throwable
	 */
	public function getTab(): string|false
	{
		return self::capture(function (): void { // @codingStandardsIgnoreLine
			require __DIR__ . '/templates/Storm.panel.tab.phtml';
			
			return;
		});
	}
	
	/**
	 * Get Storm panel
	 * @throws \Throwable
	 */
	public function getPanel(): string|false
	{
		return self::capture(function (): void {  // @codingStandardsIgnoreLine
			require __DIR__ . '/templates/Storm.panel.phtml';
			
			return;
		});
	}
	
	/**
	 * Captures PHP output into a string.
	 * @param callable $func
	 * @throws \Throwable
	 */
	public static function capture(callable $func): string|false
	{
		\ob_start();
		
		try {
			$func();
			
			return \ob_get_clean();
		} catch (\Throwable $e) {
			\ob_end_clean();
			
			throw $e;
		}
	}
}
