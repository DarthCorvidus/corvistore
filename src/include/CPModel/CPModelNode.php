<?php
class CPModelNode extends CPModelGeneric {
	public function __construct() {
		$policy = UserValue::asMandatory();
		$this->addParamUserValue("policy", $policy);
		$name = UserValue::asMandatory();
		$name->setValidate(new ValidateMinMaxString(3, 15));
		$this->addPositionalUserValue($name);
	}
}
