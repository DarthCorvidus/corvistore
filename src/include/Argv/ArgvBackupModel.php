<?php
/**
 * The model to import parameters from $argv to ArgvBackup
 *
 * @author Claus-Christoph Küthe
 */
class ArgvBackupModel implements ArgvModel {
	private $posNames = array();
	private $positional = array();
	private $named = array();
	private $boolean = array();
	public function __construct() {
		$this->positional[0] = UserValue::asMandatory();
		$this->positional[1] = UserValue::asOptional();
		$this->positional[1]->setDefault("/");
		$this->positional[1]->setConvert(new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE));
		
		$this->posNames = array("mode", "path", "target");
	}

	public function getArgNames(): array {
		return array_keys($this->named);
	}

	public function getBoolean(): array {
		return $this->boolean;
	}

	public function getNamedArg(string $name): \UserValue {
		return $this->named[$name];
	}

	public function getPositionalArg(int $i): \UserValue {
		return $this->positional[$i];
	}

	public function getPositionalCount(): int {
		return count($this->positional);
	}

	public function getPositionalName(int $i): string {
		return $this->posNames[$i];
	}

}
