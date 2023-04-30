<?php
/**
 * Import values from 'define storage' command.
 *
 * @author Claus-Christoph Küthe
 */
class CPModelStorage extends CPModelGeneric  {
	function __construct() {
		$type = UserValue::asMandatory();
		$type->setValidate(new ValidateEnum(array("basic")));
		$this->addParamUserValue("type", $type);
		
		$path = UserValue::asMandatory();
		$path->setValidate(new ValidatePath(ValidatePath::DIR));
		$this->addParamUserValue("location", $path);
		
		$description = UserValue::asOptional();
		$this->addParamUserValue("description", $description);
		
		$name = UserValue::asMandatory();
		$name->setValidate(new ValidateMinMaxString(4, 15));
		$this->addPositionalUserValue($name);
	}
}
