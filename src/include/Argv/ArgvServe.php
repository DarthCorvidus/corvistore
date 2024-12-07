<?php
/**
 * Front end for $argv values, using ArgvServeModel/Argv for import.
 *
 * @author Claus-Christoph Küthe
 */
class ArgvServe {
	private \Argv $argv;
	function __construct(array $argv) {
		$model = new ArgvServeModel();
		$this->argv = new Argv($argv, $model);
	}
	
	function hasRun(): bool {
		return $this->argv->hasValue("run");
	}
	
	function getRun(): string {
		return $this->argv->getValue("run");
	}

	function hasRunFile(): bool {
		return $this->argv->hasValue("run-file");
	}
	
	function getRunFile(): string {
		return $this->argv->getValue("run-file");
	}
	
	function hasInit(): bool {
		return $this->argv->hasValue("init");
	}
	
	function getInit(): string {
		return $this->argv->getValue("init");
	}
}
