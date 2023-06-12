<?php
namespace BinStruct;
class VersionEntry implements \BinStruct {
	private $values;
	function __construct() {
		$this->values["dvs_id"] = \IntVal::uint64LE();
		$this->values["dvs_mtime"] = \IntVal::uint32LE();
		$this->values["dvs_permissions"] = \IntVal::uint16LE();
		$this->values["dvs_owner"] = new \PrefixedStringVal(\IntVal::uint8());
		$this->values["dvs_group"] = new \PrefixedStringVal(\IntVal::uint8());
		$this->values["dvs_size"] = \IntVal::uint32LE();
		$this->values["dvs_created_epoch"] = \IntVal::uint32LE();
		$this->values["dvs_type"] = \IntVal::uint8();
		$this->values["dvs_stored"] = \IntVal::uint8();
		$this->values["dc_id"] = \IntVal::uint8();
	}

	public function getBinStruct(string $name): \BinStruct {
		
	}

	public function getBinVal(string $name): \BinVal {
		return $this->values[$name];
	}

	public function getNames(): array {
		return array_keys($this->values);
	}

	public function isBinStruct(string $name): bool {
		return false;
	}

	public function isBinVal(string $name): bool {
		return true;
	}

}