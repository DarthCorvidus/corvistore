<?php
/**
 * This is a collection of CatalogEntries, which will be used for Catalog 
 * methods that may return multiple CatalogEntries.
 *
 * @author Claus-Christoph Küthe
 */
class CatalogEntries {
	private $array;
	private $names;
	private $parentId;
	function __construct(int $parent) {
		$this->array = array();
		$this->names = array();
		$this->parentId = $parent;
	}
	
	function getParentId(): int {
		return $this->parentId;
	}
	
	function addEntry(CatalogEntry $entry) {
		$this->array[] = $entry;
		$this->names[$entry->getName()] = $this->getCount()-1;
	}
	
	function getCount(): int {
		return count($this->array);
	}
	
	function getEntry(int $id): CatalogEntry {
		return $this->array[$id];
	}
	
	function hasName(string $name): bool {
		return isset($this->names[$name]);
	}
	
	function getByName(string $name): CatalogEntry {
		return $this->array[$this->names[$name]];
	}
	
	public function getDiff(Files $files): CatFileDiff {
		$diff = new CatFileDiff($this->parentId);
		
		for($i = 0; $i<$files->getCount();$i++) {
			$file = $files->getEntry($i);
			// Determine files which are missing in the catalog.
			if(!$this->hasName($file->getBasename())) {
				$diff->addNew($file);
				continue;
			}
			// check if file is equal;
			$catalogEntry = $this->getByName($file->getBasename());
			if(!$file->isEqual($catalogEntry)) {
				$diff->addChanged($file);
			}
		}
		
		for($i = 0; $i<$this->getCount();$i++) {
			$catalogEntry = $this->getEntry($i);
			$latest = $catalogEntry->getVersions()->getLatest();
			if(!$files->hasName($catalogEntry->getName()) && $latest->getType()!= Catalog::TYPE_DELETED) {
				$diff->addDeleted($catalogEntry);
			}
		}

	return $diff;
	}
}
