<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ArgvReport
 *
 * @author hm
 */
class ArgvReport implements ArgvModel {
	private $posNames = array();
	private $positional = array();
	private $named = array();
	public function __construct() {
		$this->positional[] = UserValue::asMandatory();
		$this->positional[] = UserValue::asOptional();
		$this->posNames = array("mode", "path");

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
