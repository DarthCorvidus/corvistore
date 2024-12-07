<?php
/**
 * The model to import parameters from $argv to ArgvServe
 *
 * @author Claus-Christoph Küthe
 */
class ArgvServeModel implements ArgvModel {
	/** @var list<string> */
	private $posNames = array();
	/** @var list<UserValue> */
	private array $positional = array();
	/** @var array<string, UserValue> */
	private array $named = array();
	public function __construct() {
		$this->named["run"] = UserValue::asOptional();
		$this->named["run-file"] = UserValue::asOptional();
		$this->named["run-file"]->setValidate(new ValidatePath(ValidatePath::FILE));
		$this->named["init"] = UserValue::asOptional();
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
