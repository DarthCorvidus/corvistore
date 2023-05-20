<?php
/**
 * This class represents an object in the Catalog. It is basically an array
 * won't write to the database by itself.
 * As it is supposed to be created directly from database values, it has no
 * constructor as well.
 * 
 * @author Claus-Christoph Küthe
 */
class CatalogEntry {
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	private $pdo;
	private $id;
	private $name;
	private $parentId;
	private $nodeId;
	static function fromArray(EPDO $pdo, array $array): CatalogEntry {
		$ce = new CatalogEntry();
		$ce->pdo = $pdo;
		$ce->versions = new Versions($pdo, $ce);
		$ce->id = (int)$array["dc_id"];
		$ce->name = $array["dc_name"];
		$ce->nodeId = $array["dnd_id"];
		if($array["dc_parent"]!==NULL and $array["dc_parent"]!=="") {
			$ce->parentId = (int)$array["dc_parent"];
		}
	return $ce;
	}
	
	static function fromId(EPDO $pdo, int $id): CatalogEntry {
		$row = $pdo->row("select * from d_catalog where dc_id = ?", array($id));
		if(empty($row)) {
			throw new RuntimeException(sprintf("No catalog entry with id '%d'", $id));
		}
	return CatalogEntry::fromArray($pdo, $row);
	}
	
	static function create(EPDO $pdo, SourceObject $obj, CatalogEntry $parent = NULL): CatalogEntry {
		$name = $obj->getBasename();
		$param[] = $name;
		$param[] = $obj->getNode()->getId();
		$create["dc_name"] = $name;
		$create["dnd_id"] = $obj->getNode()->getId();
		if($parent == NULL) {
			$sql = "select * from d_catalog where dc_name = ? and dnd_id = ? and dc_parent IS NULL";
			$create["dc_parent"] = NULL;
		} else {
			$param[] = $parent->getId();
			$create["dc_parent"] = $parent->getId();
			$sql = "select * from d_catalog where dc_name = ? and dnd_id = ? and dc_parent = ?";
		}
		$row = $pdo->row($sql, $param);
		if(empty($row)) {
			$create["dc_id"] = $pdo->create("d_catalog", $create);
			$entry = CatalogEntry::fromArray($pdo, $create);
		} else {
			$entry = CatalogEntry::fromArray($pdo, $row);
		}
	return $entry;
	}
	
	function getId(): int {
		return $this->id;
	}
	
	function getName(): string {
		return $this->name;
	}
	
	function hasParentId(): bool {
		return $this->parentId !== NULL;
	}
	
	function getParentId():int {
		if($this->hasParentId()) {
			return $this->parentId;
		}
	throw new RuntimeException("Catalog entry has no parent id.");
	}
	
	function getNodeId() {
		return $this->nodeId;
	}
	
	function getVersions(): Versions {
		return $this->versions;
	}
}
