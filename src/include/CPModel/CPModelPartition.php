<?php
/**
 * Import values from 'define partition' command.
 *
 * @author Claus-Christoph Küthe
 */
class CPModelPartition extends CPModelGeneric  {
	function __construct() {
		$type = UserValue::asMandatory();
		$type->setValidate(new ValidateEnum(array("common", "active-data", "copy")));
		$this->addParamUserValue("type", $type);
		
		$storage = UserValue::asMandatory();
		$this->addParamUserValue("storage", $storage);
		
		$description = UserValue::asOptional();
		$this->addParamUserValue("description", $description);
		
		$name = UserValue::asMandatory();
		$name->setValidate(new ValidateMinMaxString(4, 20));
		$this->addPositionalUserValue($name);
	}
}
