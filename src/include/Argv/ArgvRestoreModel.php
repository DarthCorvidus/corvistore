<?php
/**
 * The model to import parameters from $argv to ArgvRestore
 *
 * @author Claus-Christoph Küthe
 */
class ArgvRestoreModel implements ArgvModel {
	private $posNames = array();
	private $positional = array();
	private $named = array();
	public function __construct() {
		$this->positional[0] = UserValue::asMandatory();
		$this->positional[1] = UserValue::asOptional();
		$this->positional[1]->setDefault("/");
		$this->positional[2] = UserValue::asOptional();
		$this->positional[2]->setConvert(new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE));
		
		$this->posNames = array("mode", "path", "target");

		$this->named["date"] = UserValue::asOptional();
		$this->named["date"]->setValidate(new ValidateDate(ValidateDate::ISO));
		$this->named["date"]->setDefault(date("Y-m-d"));
		
		$this->named["time"] = UserValue::asOptional();
		$this->named["time"]->setValidate(new ValidateTime(ValidateTime::DAY));
		$this->named["time"]->setDefault("23:59:59");
		
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
