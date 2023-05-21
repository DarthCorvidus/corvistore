<?php
/**
 * This is a collection of CatalogEntries, which will be used for Catalog 
 * methods that may return multiple CatalogEntries.
 *
 * @author Claus-Christoph Küthe
 */
class CatalogEntries {
	private $array;
	function __construct() {
		$this->array = array();
	}
	function addEntry(CatalogEntry $entry) {
		$this->array[] = $entry;
	}
	
	function getCount(): int {
		return count($this->array);
	}
	
	function getEntry(int $id): CatalogEntry {
		return $this->array[$id];
	}
	
	/**
	 * Within a collection of CatalogEntries, a directory entry will be
	 * important when recursing over a catalog, ie for restoring data.
	 * @return boolean
	 */
	function hasDir() {
		foreach($this->array as $value) {
			if($value->getType()==Catalog::TYPE_DIR) {
				return TRUE;
			}
		}
	return FALSE;
	}
	/**
	 * Return the directory within a collection of CatalogEntries; hasDir should
	 * be called first.
	 * @return \CatalogEntry
	 */
	function getDir(): CatalogEntry {
		foreach($this->array as $value) {
			if($value->getType()==Catalog::TYPE_DIR) {
				return $value;
			}
		}
	}
}
