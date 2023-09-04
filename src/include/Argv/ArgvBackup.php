<?php
/**
 * Front end for $argv values, using ArgvRestoreModel/Argv for import.
 *
 * @author Claus-Christoph Küthe
 */
class ArgvBackup {
	private $argv;
	function __construct(array $argv) {
		$model = new ArgvBackupModel();
		$this->argv = new Argv($argv, $model);
	}
	
	function getBackupPath(): string {
		if(!$this->argv->hasPositional(1)) {
			return "/";
		}
		return $this->argv->getPositional(1);
	}
}
