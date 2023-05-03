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
	private $id;
	private $name;
	private $parentId;
	private $type;
	private $nodeId;
	static function fromArray(array $array): CatalogEntry {
		$ce = new CatalogEntry();
		$ce->id = (int)$array["dc_id"];
		$ce->name = $array["dc_name"];
		$ce->nodeId = $array["dnd_id"];
		if($array["dc_parent"]!==NULL and $array["dc_parent"]!=="") {
			$ce->parentId = (int)$array["dc_parent"];
		}
		$ce->type = $array["dc_type"];
	return $ce;
	}
	
	static function fromId(EPDO $pdo, int $id): CatalogEntry {
		$row = $pdo->row("select * from d_catalog where dc_id = ?", array($id));
		if(empty($row)) {
			throw new RuntimeException(sprintf("No catalog entry with id '%d'", $id));
		}
	return CatalogEntry::fromArray($row);
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
}