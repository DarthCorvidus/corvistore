<?php
/**
 * MockupFiles
 * 
 * MockupFiles serves as a toolkit to create files in /tmp/crow-protect, to 
 * facilitate tests with files.
 *
 * @author Claus-Christoph Küthe
 */
class MockupFiles {
	private $path;
	function __construct(string $path) {
		if(!file_exists($path)) {
			mkdir($path, 0700);
		}
		$this->path = $path;
	}
	
	function delete() {
		exec("rm ".escapeshellarg($this->path)." -rf");
	}
	
	function clear() {
		$this->delete();
		mkdir($this->path, 0700);
	}
	
	function createText($path, $text) {
		$dirname = dirname($path);
		if(!file_exists($dirname)) {
			mkdir($this->path."/".$dirname, 0700, true);
		}
		file_put_contents($this->path."/".$path, $text);
	}
	
	function createRandom($path, int $size, int $blocksize=1024) {
		$dirname = dirname($path);
		if(!file_exists($dirname)) {
			mkdir($this->path."/".$dirname, 0700, true);
		}
		exec("dd if=/dev/urandom of=".escapeshellarg($this->path."/".$path)." bs=".$blocksize." count=".$size." 2> /dev/zero");
	}
}