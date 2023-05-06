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
	private $size;
	private $created;
	private $catalogId;
	static function fromArray(array $array): VersionEntry {
		$version = new VersionEntry();
		$version->id = (int)$array["dvs_id"];
		$version->atime = (int)$array["dvs_atime"];
		$version->mtime = (int)$array["dvs_mtime"];
		$version->ctime = (int)$array["dvs_ctime"];
		$version->permissions = (int)$array["dvs_permissions"];
		$version->owner = $array["dvs_owner"];
		$version->group = $array["dvs_group"];
		$version->size = (int)$array["dvs_size"];
		$version->created = (int)$array["dvs_created"];
		$version->catalogId = (int)$array["dc_id"];
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
	
	function getCatalogId(): int {
		return $this->catalogId;
	}
}
