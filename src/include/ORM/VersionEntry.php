<?php
declare(strict_types=1);
class VersionEntry {
	private $id;
	private $atime;
	private $ctime;
	private $mtime = 0;
	private $permissions = 0;
	private $owner = "";
	private $group = "";
	private $size = 0;
	private $created;
	private $catalogId;
	private $stored = 0;
	private $type;
	private function __construct() {
		
	}
	static function fromArray(array $array): VersionEntry {
		$version = new VersionEntry();
		$version->id = (int)$array["dvs_id"];
		$version->type = (int)$array["dvs_type"];
		$version->created = (int)$array["dvs_created_epoch"];
		$version->catalogId = (int)$array["dc_id"];
		
		if($version->type==Catalog::TYPE_DELETED) {
			$version->stored = 1;
			return $version;
		}
		$version->permissions = (int)$array["dvs_permissions"];
		$version->owner = $array["dvs_owner"];
		$version->group = $array["dvs_group"];
		

		
		$version->stored = (int)$array["dvs_stored"];
		if($version->type==Catalog::TYPE_FILE) {
			$version->size = (int)$array["dvs_size"];
			$version->mtime = (int)$array["dvs_mtime"];	
		}
	return $version;
	}
	
	function toBinary() {
		$values["dvs_id"] = $this->id;
		$values["dvs_type"] = $this->type;
		$values["dvs_created_epoch"] = $this->created;
		$values["dc_id"] = $this->catalogId;
		$values["dvs_permissions"] = $this->permissions;
		$values["dvs_owner"] = $this->owner;
		$values["dvs_group"] = $this->group;
		$values["dvs_size"] = $this->size;
		$values["dvs_mtime"] = $this->mtime;
		$values["dvs_stored"] = $this->stored;
	return BinaryWriter::toString($values, new \BinStruct\VersionEntry());
	}
	
	static function fromBinary($string): VersionEntry {
		$reader = new BinaryReader(new \BinStruct\VersionEntry());
		$values = $reader->fromString($string);
	return self::fromArray($values);
	}
	
	static function fromId(EPDO $pdo, int $id): VersionEntry {
		$row = $pdo->row("select * from d_version where dvs_id = ?", array($id));
		if(empty($row)) {
			throw new RuntimeException("No version with id '".$id."' found.");
		}
	return VersionEntry::fromArray($row);
	}
	
	function getId(): int {
		return $this->id;
	}
	
	function getATime(): int {
		return $this->atime;
	}
	
	function getCTime(): int {
		return $this->ctime;
	}
	
	function getMtime(): int {
		return $this->mtime;
	}

	function getPermissions(): int {
		return $this->permissions;
	}

	function getPermissionsNice(): string {
		return substr(sprintf("%o", $this->permissions), -4);
	}
	
	function getOwner(): string {
		return $this->owner;
	}
	
	function getGroup(): string {
		return $this->group;
	}
	
	function getSize(): int {
		return $this->size;
	}
	
	function getCreated(): int {
		return $this->created;
	}
	
	function getType(): int {
		return $this->type;
	}
	
	function getCatalogId(): int {
		return $this->catalogId;
	}
	
	function setStored(EPDO $pdo) {
		$pdo->update("d_version", array("dvs_stored"=>"1"), array("dvs_id"=>$this->id));
		$this->stored = 1;
	}
	
	function isStored() {
		return $this->stored === 1;
	}
}
