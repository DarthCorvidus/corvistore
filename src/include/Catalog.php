<?php
/**
 * The Catalog keeps track of all (included) files present on a node. An entry
 * is considered unique by its type, name, node and parent.
 * @author Claus-Christoph Küthe
 */
class Catalog {
	private $pdo;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	//Catchall for other types until they are implemented.
	const TYPE_OTHER = 99;
	function __construct(EPDO $pdo) {
		$this->pdo = $pdo;
	}
	
	/**
	 * loadcreate loads or creates on the fly catalog entries for a SourceObject.
	 * It will create all entries up to the root directory.
	 * Please note that this has of course quite a performance penalty, because
	 * it has to check all entries all the way up to the root directory; when
	 * 
	 * @param SourceObject $obj
	 * @return \CatalogEntry
	 * @throws Exception
	 */
	function loadcreate(SourceObject $obj): CatalogEntry {
		if($obj->getType()==self::TYPE_OTHER) {
			throw new Exception("File type not implemented yet.");
		}
		if($obj->hasParent()) {
			$parent = $obj->getParent();
			$parentCatalogEntry = $this->loadcreate($parent);
		}
		$query[] = $obj->getNode()->getId();
		$query[] = $obj->getBasename();
		$query[] = Catalog::TYPE_DIR;
		if($obj->hasParent()) {
			$query[] = $parentCatalogEntry->getId();
			$queryString = "select * from d_catalog where dnd_id = ? and dc_name = ? and dc_type = ? and dc_parent = ?";
		} else {
			$queryString = "select * from d_catalog where dnd_id = ? and dc_name = ? and dc_type = ? and dc_parent IS NULL";
		}
		$row = $this->pdo->row($queryString, $query);
		if(!empty($row)) {
			return CatalogEntry::fromArray($this->pdo, $row);
		}
		if($obj->hasParent()) {
			return $this->create($obj, $parentCatalogEntry);
		} else {
			return $this->create($obj);
		}
	}
	/**
	 * loadcreateParented trusts the calling process to have the proper parent.
	 * This can be assumed to be the case if it traverses a file system with
	 * recursion; in this case, the performance penalty of loadcreate() would be
	 * terrible, as loadcreate has to follow a path up to the root directory
	 * whenever it is called.
	 * @param SourceObject $obj
	 * @param CatalogEntry $parent
	 * @return type
	 * @throws Exception
	 */
	function loadcreateParented(SourceObject $obj, CatalogEntry $parent) {
		if($obj->getType()==self::TYPE_OTHER) {
			throw new Exception("File type not implemented yet.");
		}
		$query[] = $obj->getNode()->getId();
		$query[] = $obj->getBasename();
		$query[] = $parent->getId();
		$queryString = "select * from d_catalog where dnd_id = ? and dc_name = ? and dc_parent = ?";
		$row = $this->pdo->row($queryString, $query);
		if(!empty($row)) {
			return CatalogEntry::fromArray($this->pdo, $row);
		}
	return $this->create($obj, $parent);
	}
	
	private function create(SourceObject $obj, CatalogEntry $parent = NULL): CatalogEntry {
		$new["dc_name"] = $obj->getBasename();
		$new["dnd_id"] = $obj->getNode()->getId();
		$new["dc_type"] = $obj->getType();
		if($parent!=NULL) {
			$new["dc_parent"] = $parent->getId();
		} else {
			$new["dc_parent"] = NULL;
		}
		$new["dc_id"] = $this->pdo->create("d_catalog", $new);
	return CatalogEntry::fromArray($this->pdo, $new);
	}
	
	function getEntryByPath(Node $node, string $path) {
		$exp = array_slice(explode("/", $path), 1);
		$entry = NULL;
		foreach($exp as $value) {
			if($entry==NULL) {
				$entry = CatalogEntry::fromName($this->pdo, $node, $value, $entry);
			} else {
				$entry = CatalogEntry::fromName($this->pdo, $node, $value, $entry);
			}
		}
	return $entry;
	}
}
