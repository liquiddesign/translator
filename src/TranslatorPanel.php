<?php

namespace Translator;

use Latte\Runtime\Template;
use Tracy\IBarPanel;

class TranslatorPanel implements IBarPanel
{
	private string $currentMutation;
	
	private string $allMutations = '[';
	
	private string $untranslated = '';
	
	private int $untranslatedCount;
	
	public function __construct($mutation, $allMutations, $untranslated)
	{
		$this->currentMutation = $mutation;
		$this->untranslatedCount = count($untranslated);
		foreach (array_keys($allMutations) as $mutation) {
			$this->allMutations .= "$mutation,";
		}
		$this->allMutations .= ']';
		foreach ($untranslated as $key => $value) {
			$this->untranslated .= "$key: $value<br>";
		}
		
	}
	
	/**
	 * @inheritDoc
	 */
	function getTab()
	{
		
		return "<span title=\"$this->currentMutation\">
					<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"feather feather-flag\"><path d=\"M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z\"></path><line x1=\"4\" y1=\"22\" x2=\"4\" y2=\"15\"></line></svg>
					<span class=\"tracy-label\">$this->currentMutation</span>
				</span>";
	}
	
	/**
	 * @inheritDoc
	 */
	function getPanel()
	{
		return "<h1>Translator</h1>
				<div class=\"tracy-inner\">
				<div class=\"tracy-inner-container\">
					Available mutations: $this->allMutations<br>
					Untranslated: $this->untranslatedCount<br>
					Untranslated strings: <br>$this->untranslated
				</div>
				</div>
		";
	}
}