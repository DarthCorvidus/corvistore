<?php
/**
 * CPModelGeneric
 * 
 * Generic implementation of CPModel, to prevent writing the same code over and
 * over again.
 *
 * @author Claus-Christoph Küthe
 */
class CPModelGeneric implements CPModel {
	/** @var array<string, UserValue> */
	private array $params = array();
	/** @var list<UserValue> */
	private array $positional = array();
	protected function addParamUserValue(string $param, UserValue $value): void {
		$this->params[$param] = $value;
	}

	protected function addPositionalUserValue(UserValue $value): void {
		$this->positional[] = $value;
	}
	
	public function getParamUserValue(string $param): \UserValue {
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
