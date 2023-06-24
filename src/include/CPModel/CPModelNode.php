<?php
class CPModelNode extends CPModelGeneric {
	const MODE_DEFINE = 1;
	const MODE_UPDATE = 2;
	public function __construct(EPDO $pdo, int $mode = self::MODE_DEFINE) {
		if($mode == self::MODE_DEFINE) {
			$policy = UserValue::asMandatory();
		} 
		if($mode == self::MODE_UPDATE) {
			$policy = UserValue::asOptional();
			$this->addParamUserValue("policy", $policy);
		}
	
		$password = UserValue::asOptional();
		$this->addParamUserValue("password", $password);
		$name = UserValue::asMandatory();
		$name->setValidate(new ValidateMinMaxString(3, 15));
		$this->addPositionalUserValue($name);
		
	}
}
