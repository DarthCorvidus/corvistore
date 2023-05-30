<?php
/**
 * Import values from 'define partition' command.
 *
 * @author Claus-Christoph Küthe
 */
class CPModelPartition extends CPModelGeneric  {
	const MODE_DEFINE = 1;
	const MODE_UPDATE = 2;
	function __construct(EPDO $pdo, int $mode) {
		$type = UserValue::asMandatory();
		if($mode==self::MODE_DEFINE) {
			$type->setValidate(new ValidateEnum(array("common", "active-data", "copy")));
			$this->addParamUserValue("type", $type);
			
			$storage = UserValue::asMandatory();
			$this->addParamUserValue("storage", $storage);
		}
		
		
		$description = UserValue::asOptional();
		$this->addParamUserValue("description", $description);

		$copy = UserValue::asOptional();
		$this->addParamUserValue("copy", $copy);
		
		$name = UserValue::asMandatory();
		$name->setValidate(new ValidateMinMaxString(4, 20));
		$this->addPositionalUserValue($name);
		
		
	}
}
