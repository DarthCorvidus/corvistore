<?php
class Files {
	private $entries = array();
	function __construct() {
		
	}
	
	function addEntry(File $file) {
		$this->entries[] = $file;
	}
	
	function getCount(): int {
		return count($this->entries);
	}
	
	function getEntry(int $id): FileEntry {
		$this->entries[$id];
	}
}