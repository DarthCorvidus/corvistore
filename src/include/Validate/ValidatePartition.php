<?php
class ValidatePartition implements Validate {
	private $expectExist = false;
	private $expectCopy = false;
	function __construct(EPDO $pdo) {
		;
	}
	
	function expectExist(bool $bool) {
		$this->expectExist = $bool;
	}
	
	function expectHasCopy(bool $bool) {
		$this->expectCopy = $bool;
	}
	
	function expectIsCopy(bool $bool) {
		$this->expectCopy = $bool;
	}

	
	function validate(string $partition) {
		
	}
}
