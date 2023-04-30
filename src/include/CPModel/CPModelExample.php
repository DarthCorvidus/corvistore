<?php
/**
 * CPModelExample
 * 
 * Test implementation of CPModel to be used in testing 
 *
 * @author Claus-Christoph Küthe
 */
class CPModelExample extends CPModelGeneric {
	function __construct() {
		$type = UserValue::asMandatory();
		$type->setValidate(new ValidateEnum(array("basic")));
		$this->addParamUserValue("type", $type);
		
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$this->addParamUserValue("location", $location);
		
		$description = UserValue::asOptional();
		$this->addParamUserValue("description", $description);
		$this->addPositionalUserValue(UserValue::asMandatory());
	}
}
