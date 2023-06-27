<?php
class CPModelUser extends CPModelGeneric {
	const MODE_DEFINE = 1;
	const MODE_UPDATE = 2;
	public function __construct(EPDO $pdo, int $mode = self::MODE_DEFINE) {
		if(self::MODE_DEFINE) {
			$password = UserValue::asMandatory();
		} else {
			$password = UserValue::asOptional();
		}
		$this->addParamUserValue("password", $password);
		$name = UserValue::asMandatory();
		$name->setValidate(new ValidateMinMaxString(3, 15));
		$this->addPositionalUserValue($name);
	}
}
