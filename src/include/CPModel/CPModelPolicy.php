<?php
/**
 * Import values from 'define policy' command.
 *
 * @author Claus-Christoph Küthe
 */
class CPModelPolicy extends CPModelGeneric  {
	function __construct() {
		
		$verexists = UserValue::asOptional();
		$verexists->setValidate(new ValidateInteger());
		$verexists->setDefault(0);
		$this->addParamUserValue("verexists", $verexists);

		$verdelete = UserValue::asOptional();
		$verdelete->setDefault(0);
		$verdelete->setValidate(new ValidateInteger());
		$this->addParamUserValue("verdeleted", $verdelete);

		$retexists = UserValue::asOptional();
		$retexists->setDefault(0);
		$retexists->setValidate(new ValidateInteger());
		$this->addParamUserValue("retexists", $retexists);

		$retdelete = UserValue::asOptional();
		$retdelete->setDefault(0);
		$retdelete->setValidate(new ValidateInteger());
		$this->addParamUserValue("retdeleted", $retdelete);
		
		$this->addParamUserValue("partition", UserValue::asMandatory());
		$name = UserValue::asMandatory();
		$name->setValidate(new ValidateMinMaxString(4, 15));
		$this->addPositionalUserValue($name);
	}
}
