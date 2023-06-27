<?php
/**
 * Import values from 'define partition' command.
 *
 * @author Claus-Christoph Küthe
 */
class CPModelBackup extends CPModelGeneric  {
	function __construct(EPDO $pdo) {
		$name = UserValue::asMandatory();
		$name->setValidate(new ValidateMinMaxString(4, 20));
		$this->addPositionalUserValue($name);
	}
}
