<?php
interface BinaryPersistable {
	function toBinary(): string;
	static function fromBinary(string $binary): BinaryPersistable;
}