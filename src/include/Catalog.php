<?php
/**
 * The Catalog keeps track of all (included) files present on a node. An entry
 * is considered unique by its type, name, node and parent.
 * @author Claus-Christoph Küthe
 */
class Catalog {
	private $pdo;
	private $node;
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	//Catchall for other types until they are implemented.
	const TYPE_OTHER = 99;
	function __construct(EPDO $pdo, Node $node) {
		$this->pdo = $pdo;
		$this->node = $node;
	}

	function getEntries(string $dirname): CatalogEntries {
		$entries = new CatalogEntries($dirname);
		$param = array();
		$param[] = 1;
		$param[] = $this->node->getId();
		#if($parent===0) {
		#	$stmt = $this->pdo->prepare("select * from d_catalog JOIN d_version USING (dc_id) where dvs_stored = ? and dnd_id = ? AND dc_parent IS NULL order by dc_id, dvs_created_epoch");
		#} else {
		#	$stmt = $this->pdo->prepare("select * from d_catalog JOIN d_version USING (dc_id) where dvs_stored = ? and dnd_id = ? AND dc_parent = ? order by dc_id, dvs_created_epoch");
		#	$param[] = $parent;
		#}
		$stmt = $this->pdo->prepare("select * from d_catalog JOIN d_version USING (dc_id) where dvs_stored = ? and dnd_id = ? AND dc_dirname = ? order by dc_id, dvs_created_epoch");
		$param[] = $dirname;
		$stmt->setFetchMode(EPDO::FETCH_ASSOC);
		$stmt->execute($param);
		$tmp = array();
		foreach($stmt as $key => $value) {
			if(!$entries->hasName($value["dc_name"])) {
				$entry = new CatalogEntry($value);
				$entries->addEntry($entry);
			}
			$entry = $entries->getByName($value["dc_name"]);
			$entry->addVersion($value);
		}
	return $entries;
	}
	
	function newEntry(File $file, int $parent = 0): CatalogEntry {
		if($file->getType() == Catalog::TYPE_DIR) {
			return $this->newEntryDir($file, $parent);
		}
		if($file->getType() == Catalog::TYPE_FILE) {
			return $this->newEntryFile($file, $parent);
		}

	}
	
	private function newEntryDir(File $file, int $parent = 0): CatalogEntry {
		$create["dc_name"] = $file->getBasename();
		$create["dc_dirname"] = $file->getDirname();
		$create["dnd_id"] = $this->node->getId();
		if($parent!==0) {
			$create["dc_parent"] = $parent;
		}
		$create["dc_id"] = $this->pdo->create("d_catalog", $create);
		$version["dc_id"] = $create["dc_id"];
		$version["dvs_owner"] = $file->getOwner();
		$version["dvs_group"] = $file->getGroup();
		$version["dvs_type"] = Catalog::TYPE_DIR;
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = time();
		$version["dvs_permissions"] = $file->getPerms();
		// Directories are always 'stored'.
		$version["dvs_stored"] = 1;
		$version["dvs_id"] = $this->pdo->create("d_version", $version);
		$entry = new CatalogEntry($create);
		$entry->addVersion($version);
	return $entry;
	}
	
	private function newEntryFile(File $file, int $parent = 0) {
		$create["dc_dirname"] = $file->getDirname();
		$create["dc_name"] = $file->getBasename();
		$create["dnd_id"] = $this->node->getId();
		if($parent!==0) {
			$create["dc_parent"] = $parent;
		}
		$create["dc_id"] = $this->pdo->create("d_catalog", $create);
		$version["dc_id"] = $create["dc_id"];
		$version["dvs_owner"] = $file->getOwner();
		$version["dvs_group"] = $file->getGroup();
		$version["dvs_mtime"] = $file->getMTime();
		$version["dvs_size"] = $file->getSize();
		$version["dvs_type"] = Catalog::TYPE_FILE;
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = time();
		$version["dvs_permissions"] = $file->getPerms();
		$version["dvs_stored"] = 0;
		$version["dvs_id"] = $this->pdo->create("d_version", $version);
		$entry = new CatalogEntry($create);
		$entry->addVersion($version);
	return $entry;
	}
	
	function updateEntry(CatalogEntry $entry, File $file) {
		$version["dc_id"] = $entry->getId();
		$version["dvs_owner"] = $file->getOwner();
		$version["dvs_group"] = $file->getGroup();
		$version["dvs_stored"] = 1;
		//mtime and size are only relevant for files.
		if($file->getType()==Catalog::TYPE_FILE) {
			$version["dvs_size"] = $file->getSize();
			$version["dvs_mtime"] = $file->getMTime();
			$version["dvs_stored"] = 0;
		}
		$version["dvs_type"] = $file->getType();
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = time();
		$version["dvs_permissions"] = $file->getPerms();
		
		$version["dvs_id"] = $this->pdo->create("d_version", $version);
		$entry->addVersion($version);
	return $entry;
	}
	
	function deleteEntry(CatalogEntry $entry) {
		$version["dc_id"] = $entry->getId();
		$version["dvs_stored"] = 1;
		$version["dvs_type"] = self::TYPE_DELETED;
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = time();
		$version["dvs_id"] = $this->pdo->create("d_version", $version);
		$entry->addVersion($version);
	return $entry;
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
			/*
			 * trailing or double slashes can lead to an empty value within the
			 * array. Ignore them. 
			 */
			if($value==NULL) {
				continue;
			}
			if($entry==NULL) {
				$entry = CatalogEntry::fromName($this->pdo, $node, $value, $entry);
			} else {
				$entry = CatalogEntry::fromName($this->pdo, $node, $value, $entry);
			}
		}
	return $entry;
	}
}
