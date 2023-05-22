<?php
/**
 * Front end for $argv values, using ArgvRestoreModel/Argv for import.
 *
 * @author Claus-Christoph Küthe
 */
class ArgvRestore {
	private $argv;
	function __construct(array $argv) {
		$model = new ArgvRestoreModel();
		$this->argv = new Argv($argv, $model);
	}
	
	function getRestorePath(): string {
		return $this->argv->getPositional(1);
	}

	function getTargetPath(): string {
		if(!$this->argv->hasPositional(2)) {
			return "";
		}
		return $this->argv->getPositional(2);
	}
}
