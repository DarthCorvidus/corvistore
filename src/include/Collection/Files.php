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
	
	function getEntry(int $id): File {
		$this->entries[$id];
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