<?php
/**
 * Model to give UserValue validation to CommandParser
 *
 * @author Claus-Christoph Küthe
 */
interface CPModel {
	function getParameters(): array;
	function getParamUserValue($param): UserValue;
	function getPositionalCount(): int;
	function getPositionalUserValue(int $pos): UserValue;
}