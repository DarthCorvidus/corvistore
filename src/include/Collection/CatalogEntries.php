<?php
/**
 * This is a collection of CatalogEntries, which will be used for Catalog 
 * methods that may return multiple CatalogEntries.
 *
 * @author Claus-Christoph Küthe
 */
class CatalogEntries {
	/** @var list<CatalogEntry> */
	private array $array;
	/** @var array<string, int> */
	private array $names;
	private string $dirname;
	function __construct(string $dirname) {
		$this->array = array();
		$this->names = array();
		$this->dirname = $dirname;
	}
	
	function getDirname(): string {
		return $this->dirname;
	}
	
	function addEntry(CatalogEntry $entry): void {
		if(isset($this->names[$entry->getName()])) {
			throw new \RuntimeException("duplicate entry ".$entry->getName()." added");
		}
		$this->array[] = $entry;
		$this->names[$entry->getName()] = $this->getCount()-1;
	}
	
	function getCount(): int {
		return count($this->array);
	}
	
	function getEntry(int $id): CatalogEntry {
		if(!isset($this->array[$id])) {
			throw new \OutOfBoundsException("no entry with id ".$id);
		}
		return $this->array[$id];
	}
	
	function hasName(string $name): bool {
		return isset($this->names[$name]);
	}
	
	function getByName(string $name): CatalogEntry {
		if(!$this->hasName($name)) {
			throw new \InvalidArgumentException("CatalogEntry with name '".$name."' does not exist");
		}
		return $this->array[$this->names[$name]];
	}
	
	public function getDiff(Files $files): CatFileDiff {
		$diff = new CatFileDiff($this->dirname);
		
		for($i = 0; $i<$files->getCount();$i++) {
			$file = $files->getEntry($i);
			// Determine files which are missing in the catalog.
			if(!$this->hasName($file->getBasename())) {
				$diff->addClientOnly($file);
				continue;
			}
			// check if file is equal;
			$catalogEntry = $this->getByName($file->getBasename());
			if(!$file->isEqual($catalogEntry)) {
				$diff->addDifferent($file, $catalogEntry);
			}
		}
		
		for($i = 0; $i<$this->getCount();$i++) {
			$catalogEntry = $this->getEntry($i);
			$latest = $catalogEntry->getVersions()->getLatest();
			if(!$files->hasName($catalogEntry->getName()) && $latest->getType()!= Catalog::TYPE_DELETED) {
				$diff->addServerOnly($catalogEntry);
			}
		}

	return $diff;
	}
}
