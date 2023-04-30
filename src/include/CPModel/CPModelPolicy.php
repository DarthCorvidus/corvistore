<?php
/**
 * Import values from 'define policy' command.
 *
 * @author Claus-Christoph Küthe
 */
class CPModelPolicy extends CPModelGeneric  {
	function __construct() {
		
		$verexists = UserValue::asMandatory();
		$type->setValidate(new ValidateInteger());
		$this->addParamUserValue("verexists", $type);
		
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
