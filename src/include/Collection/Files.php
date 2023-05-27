<?php
class Files {
	private $entries = array();
	private $names = array();
	function __construct() {
		
	}
	
	function addEntry(File $file) {
		$this->entries[] = $file;
		$this->names[$file->getBasename()] = $this->getCount()-1;
	}
	
	function getCount(): int {
		return count($this->entries);
	}
	
	function getEntry(int $id): File {
		$this->entries[$id];
	}
	
	function hasName(string $name): bool {
		return isset($this->names[$name]);
	}
	
	function getByName(string $name): File {
		return $this->entries[$this->names[$name]];
	}
	
	function getDirectories(): Files {
		$files = new Files();
		foreach($this->entries as $value) {
			if($value->getType() == Catalog::TYPE_DIR) {
				$files->addEntry($value);
			}
		}
	return $files;
	}
}