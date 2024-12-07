<?php
/**
 * The model to import parameters from $argv to ArgvBackup
 *
 * @author Claus-Christoph Küthe
 */
class ArgvBackupModel implements ArgvModel {
	/** @var list<string> */
	private $posNames = array();
	/** @var list<UserValue> */
	private array $positional = array();
	/** @var array<string, UserValue> */
	private array $named = array();
	/** @var list<string> */
	private $boolean = array();
	public function __construct() {
		$this->positional[] = UserValue::asMandatory();
		$this->positional[] = UserValue::asOptional();
		$this->positional[1]->setDefault("/");
		$this->positional[1]->setConvert(new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE));
		
		$this->posNames = array("mode", "path", "target");
		
		$this->named["include-list"] = UserValue::asOptional();
		$this->named["include-list"]->setValidate(new ValidatePath(ValidatePath::FILE));
		
		$this->named["exclude-list"] = UserValue::asOptional();
		$this->named["exclude-list"]->setValidate(new ValidatePath(ValidatePath::FILE));
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
