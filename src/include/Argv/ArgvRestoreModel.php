<?php
/**
 * The model to import parameters from $argv to ArgvRestore
 *
 * @author Claus-Christoph Küthe
 */
class ArgvRestoreModel implements ArgvModel {
	/** @var list<string> */
	private array $posNames = array();
	/** @var list<UserValue> */
	private $positional = array();
	/** @var array<string, UserValue> */
	private $named = array();
	/** @var list<string> */
	private $boolean = array();
	public function __construct() {
		$this->positional[] = UserValue::asMandatory();
		$this->positional[] = UserValue::asOptional();
		$this->positional[1]->setDefault("/");
		$this->positional[] = UserValue::asOptional();
		$this->positional[2]->setConvert(new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE));
		
		$this->posNames = array("mode", "path", "target");

		$this->named["date"] = UserValue::asOptional();
		$this->named["date"]->setValidate(new ValidateDate(ValidateDate::ISO));
		$this->named["date"]->setDefault(date("Y-m-d"));
		
		$this->named["time"] = UserValue::asOptional();
		$this->named["time"]->setValidate(new ValidateTime(ValidateTime::DAY));
		$this->named["time"]->setDefault("23:59:59");
		
		$this->boolean = array("skip");
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
