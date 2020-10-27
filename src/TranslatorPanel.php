<?php

namespace Translator;

use Tracy\IBarPanel;

class TranslatorPanel implements IBarPanel
{
	private string $currentMutation;
	
	public function __construct($mutation)
	{
		$this->currentMutation=$mutation;
	}
	
	/**
	 * @inheritDoc
	 */
	function getTab()
	{
		return "Translator: $this->currentMutation";
	}
	
	/**
	 * @inheritDoc
	 */
	function getPanel()
	{
	
	}
}