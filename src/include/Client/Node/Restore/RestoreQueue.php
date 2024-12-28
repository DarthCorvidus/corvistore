<?php
namespace Node;
class RestoreQueue {
	public int $expectedDirs = 0;
	public int $expectedFiles = 0;
	public array $directories = array();
	public array $versions = array();
	public function isEmpty(): bool {
		if($this->expectedDirs > 0) {
			return false;
		}
		
		if($this->expectedFiles > 0) {
			return false;
		}
		
		if(!empty($this->directories)) {
			return false;
		}
		
		if(!empty($this->versions)) {
			return false;
		}
	return true;
	}
}