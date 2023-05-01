<?php
/**
 * CPModelGeneric
 * 
 * Generic implementation of CPModel, to prevent writing the same code over and
 * over again.
 *
 * @author Claus-Christoph Küthe
 */
class CPModelTesting implements CPModel {
	private $params = array();
	private $positional = array();
	public function addParamUserValue(string $param, UserValue $value) {
		$this->params[$param] = $value;
	}

	public function addPositionalUserValue(UserValue $value) {
		$this->positional[] = $value;
	}
	
	public function getParamUserValue($param): \UserValue {
		return $this->params[$param];
	}

	public function getParameters(): array {
		return array_keys($this->params);
	}

	public function getPositionalCount(): int {
		return count($this->positional);
	}

	public function getPositionalUserValue(int $pos): \UserValue {
		return $this->positional[$pos];
	}

}
