<?php
/**
 * Front end for $argv values, using ArgvRestoreModel/Argv for import.
 *
 * @author Claus-Christoph Küthe
 */
class ArgvBackup {
	private Argv $argv;
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
	
	function hasExcludeList(): bool {
		return $this->argv->hasValue("exclude-list");
	}

	function getExcludeList(): string {
		return $this->argv->getValue("exclude-list");
	}
	
	function hasIncludeList(): bool {
		return $this->argv->hasValue("include-list");
	}
	
	function getIncludeList(): string {
		return $this->argv->getValue("include-list");
	}

}
