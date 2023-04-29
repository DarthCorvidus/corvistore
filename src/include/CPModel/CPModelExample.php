<?php
/**
 * CPModelExample
 * 
 * Test implementation of CPModel to be used in testing 
 *
 * @author Claus-Christoph Küthe
 */
class CPModelExample implements CPModel {
	private $parameters = array();
	private $positional = array();
	function __construct() {
		$type = UserValue::asMandatory();
		$type->setValidate(new ValidateEnum(array("basic")));
		$this->parameters["type"] = $type;

		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$this->parameters["location"] = $location;
		
		$description = UserValue::asOptional();
		
		$this->parameters["description"] = $description;
		
		
		$this->positional[0] = UserValue::asMandatory();
	}
	public function getParameters(): array {
		return array_keys($this->parameters);
	}

	public function getParamUserValue($param): \UserValue {
		return $this->parameters[$param];
	}

	public function getPositionalCount(): int {
		return count($this->positional);
	}

	public function getPositionalUserValue(int $pos): \UserValue {
		return $this->positional[$pos];
	}

}
