<?php
/**
 * Front end for $argv values, using ArgvAdminModel/Argv for import.
 *
 * @author Claus-Christoph Küthe
 */
class ArgvAdmin {
	private $argv;
	function __construct(array $argv) {
		$model = new ArgvAdminModel();
		$this->argv = new Argv($argv, $model);
	}
	
	function hasUsername(): bool {
		return $this->argv->hasValue("username");
	}
	
	function getUsername(): string {
		return $this->argv->getValue("username");
	}

	function hasPassword(): bool {
		return $this->argv->hasValue("password");
	}
	
	function getPassword(): string {
		return $this->argv->getValue("password");
	}
}
