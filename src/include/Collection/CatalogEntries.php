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
	function __construct() {
		$this->array = array();
		$this->names = array();
	}
	function addEntry(CatalogEntry $entry) {
		$this->array[] = $entry;
		$this->names[$this->getCount()] = $entry->getName();
	}
	
	function getCount(): int {
		return count($this->array);
	}
	
	function getEntry(int $id): CatalogEntry {
		return $this->array[$id];
	}
	
	function hasName(string $name): bool {
		return in_array($name, $this->names);
	}
	
	function getByName(string $name): CatalogEntry {
		return $this->array[$this->names[$name]];
	}
}
