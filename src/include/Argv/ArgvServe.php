<?php
/**
 * Front end for $argv values, using ArgvServeModel/Argv for import.
 *
 * @author Claus-Christoph Küthe
 */
class ArgvServe {
	private $argv;
	function __construct(array $argv) {
		$model = new ArgvServeModel();
		$this->argv = new Argv($argv, $model);
	}
	
	function hasRunPath(): bool {
		return $this->argv->hasValue("run");
	}
	
	function getRunPath(): string {
		return $this->argv->getValue("run");
	}
}
