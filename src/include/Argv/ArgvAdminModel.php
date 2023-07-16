<?php
/**
 * The model to import parameters from $argv to ArgvAdmin
 *
 * @author Claus-Christoph Küthe
 */
class ArgvAdminModel implements ArgvModel {
	private $posNames = array();
	private $positional = array();
	private $named = array();
	public function __construct() {
		$this->named["username"] = UserValue::asOptional();
		$this->named["password"] = UserValue::asOptional();
	}

	public function getArgNames(): array {
		return array_keys($this->named);
	}

	public function getBoolean(): array {
		return array();
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
