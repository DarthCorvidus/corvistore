<?php
/**
 * Versions are attached to every catalog entry. Their ID will be used to store
 * a file.
 * @author Claus-Christoph Küthe
 */

class Version {
	private $pdo;
	private $catalogEntry;
	#private $sourceObject;
	function __construct(EPDO $pdo, CatalogEntry $catalogEntry) {
		$this->pdo = $pdo;
		$this->catalogEntry = $catalogEntry;
		#$this->sourceObject = $sourceObject;
	}
	function addVersion(SourceObject $object): VersionEntry {
		#$param[] = $object->getCTime();
		$param[] = $this->catalogEntry->getId();
		$param[] = "1";
		#$param[] = $object->getSize();
		#$subquery = "select max(dvs_created) from d_version where dvs_ctime = ? and dc_id = ? and dvs_size = ?";
		#$param[] = $object->getCTime();
		#$param[] = $this->catalogEntry->getId();
		#$param[] = $object->getSize();
		#$row = $this->pdo->row("select * from d_version where dvs_ctime = ? and dc_id = ? and dvs_size = ? and dvs_created = ($subquery)", $param);
		/*
		 * Get the latest version of a specific CatalogEntry. If mtime and size are the same, return existing version, otherwise create a new one.
		 */
		$row = $this->pdo->row("select * from d_version where dc_id = ? and dvs_stored = ? order by dvs_created DESC LIMIT 1", $param);
		if(!empty($row) && $row["dvs_mtime"]==$object->getMTime() && $row["dvs_size"]==$object->getSize()) {
			return VersionEntry::fromArray($row);
		}
		$new["dvs_atime"] = $object->getATime();
		$new["dvs_mtime"] = $object->getMTime();
		$new["dvs_ctime"] = $object->getCTime();
		$new["dvs_permissions"] = $object->getPerms();
		$new["dvs_owner"] = $object->getOwner();
		$new["dvs_group"] = $object->getGroup();
		$new["dvs_size"] = $object->getSize();
		$new["dvs_created"] = mktime();
		$new["dvs_stored"] = "0";
		$new["dvs_deleted"] = "0";
		$new["dc_id"] = $this->catalogEntry->getId();
		$new["dvs_id"] = $this->pdo->create("d_version", $new);
	return VersionEntry::fromArray($new);
	}
	
	function setActive(VersionEntry $version) {
		
	}
	
	function setStored(VersionEntry $version) {
		$this->pdo->update("d_version", array("dvs_stored" => 1), array("dvs_id"=>$version->getId()));
	}
	
	function getLatest() {
		
	}
}