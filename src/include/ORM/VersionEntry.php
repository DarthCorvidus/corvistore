<?php
declare(strict_types=1);
class VersionEntry {
	private $id;
	private $atime;
	private $ctime;
	private $mtime;
	private $permissions;
	private $owner;
	private $group;
	private $size = 0;
	private $created;
	private $catalogId;
	private $stored;
	private $type;
	private function __construct() {
		
	}
	static function fromArray(array $array): VersionEntry {
		$version = new VersionEntry();
		$version->id = (int)$array["dvs_id"];
		$version->type = (int)$array["dvs_type"];
		$version->created = (int)$array["dvs_created_epoch"];
		
		if($version->type==Catalog::TYPE_DELETED) {
			return $version;
		}
		$version->permissions = (int)$array["dvs_permissions"];
		$version->owner = $array["dvs_owner"];
		$version->group = $array["dvs_group"];
		

		$version->catalogId = (int)$array["dc_id"];
		$version->stored = (int)$array["dvs_stored"];
		if($version->type==Catalog::TYPE_FILE) {
			$version->size = (int)$array["dvs_size"];
			$version->mtime = (int)$array["dvs_mtime"];	
		}
	return $version;
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
