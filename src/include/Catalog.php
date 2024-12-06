<?php
/**
 * The Catalog keeps track of all (included) files present on a node. An entry
 * is considered unique by its type, name, node and parent.
 * @author Claus-Christoph Küthe
 */
class Catalog {
	private \EPDO $pdo;
	private \Node $node;
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	const TYPE_LINK = 3;
	//Catchall for other types until they are implemented.
	const TYPE_OTHER = 99;
	function __construct(\EPDO $pdo, \Node $node) {
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
		if($file->getType() == Catalog::TYPE_FILE or $file->getType() == Catalog::TYPE_LINK) {
			return $this->newEntryFile($file, $parent);
		}
	// should not trip.
	throw new \RuntimeException("Invalid file type ".$file->getType());
	}
	
	private function newEntryDir(File $file, int $parent = 0): CatalogEntry {
		$create = array();
		$create["dc_name"] = $file->getBasename();
		$create["dc_dirname"] = $file->getDirname();
		$create["dnd_id"] = $this->node->getId();
		if($parent!==0) {
			$create["dc_parent"] = $parent;
		}
		$create["dc_id"] = $this->pdo->create("d_catalog", $create);
		
		$version = array();
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
	
	private function newEntryFile(File $file, int $parent = 0): CatalogEntry {
		$param = [];
		$param[] = $file->getDirname();
		$param[] = $file->getBasename();
		$param[] = $this->node->getId();
		$create = $this->pdo->row("select * from d_catalog where dc_dirname = ? and dc_name = ? and dnd_id = ?", $param);
		if(empty($create)) {
			$create["dc_dirname"] = $file->getDirname();
			$create["dc_name"] = $file->getBasename();
			$create["dnd_id"] = $this->node->getId();
			if($parent!==0) {
				$create["dc_parent"] = $parent;
			}
			$create["dc_id"] = $this->pdo->create("d_catalog", $create);
		}

		$version = array();
		$version["dc_id"] = $create["dc_id"];
		$version["dvs_owner"] = $file->getOwner();
		$version["dvs_group"] = $file->getGroup();
		$version["dvs_mtime"] = $file->getMTime();
		$version["dvs_size"] = $file->getSize();
		$version["dvs_type"] = $file->getType();
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = time();
		$version["dvs_permissions"] = $file->getPerms();
		$version["dvs_stored"] = 0;
		$version["dvs_id"] = $this->pdo->create("d_version", $version);
		$entry = new CatalogEntry($create);
		$entry->addVersion($version);
	return $entry;
	}
	
	function updateEntry(int $entryId, File $file): VersionEntry {
		$version = array();
		$version["dc_id"] = $entryId;
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
		$version = VersionEntry::fromArray($version);
	return $version;
	}
	
	function deleteEntry(int $catalogId): void {
		$version = array();
		$version["dc_id"] = $catalogId;
		$version["dvs_stored"] = 1;
		$version["dvs_type"] = self::TYPE_DELETED;
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = time();
		$version["dvs_id"] = $this->pdo->create("d_version", $version);
		#$entry->addVersion($version);
	#return $entry;
	}
	
	function getEntryByPath(string $path): CatalogEntry {
		$param = array();
		$param[] = $this->node->getId();
		$param[] = dirname($path);
		$param[] = basename($path);
		$param[] = 1;
		$query = array();
		$query[] = "select * from d_catalog JOIN d_version USING (dc_id)";
		$query[] = "WHERE dnd_id = ? and dc_dirname = ? and dc_name = ? and dvs_stored = ?";
		$query[] = "ORDER BY dc_id, dvs_created_epoch DESC";
		$stmt = $this->pdo->prepare(implode(" ", $query));
		$stmt->execute($param);
		$entry = null;
		foreach($stmt as $key => $value) {
			if($key === 0) {
				$entry = new CatalogEntry($value);
				$entry->addVersion($value);
				continue;
			}
			/** @psalm-suppress PossiblyNullReference */
			$entry->addVersion($value);
		}
		if(empty($entry)) {
		throw new \RuntimeException("unable to get catalog entry for ".$path);
		}
	return $entry;
	}
}
