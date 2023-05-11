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
		$convert = new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE);
		$this->path = $convert->convert($path);
	}
	
	function getInternalPath($path) {
		if($path[0]!="/") {
			$path = "/".$path;
		}
		return $this->path.$path;
	}
	
	function delete() {
		if(file_exists($this->path)) {
			$this->deleteRecurse($this->path);
		}
	}
	
	private function deleteRecurse($path) {
		foreach(glob($path."/*") as $key => $value) {
			if(is_dir($value)) {
				$this->deleteRecurse($value);
			}
			if(is_file($value)) {
				unlink($value);
			}
		}
		rmdir($path);
	}
	
	function clear() {
		$this->delete();
		mkdir($this->path, 0700);
	}

	function createDir($path) {
		if($path=="/" || $path==".") {
			return;
		}
		if(!file_exists($this->getInternalPath($path))) {
			mkdir($this->getInternalPath($path), 0700, true);
			clearstatcache();
		}
		if(!is_dir($this->getInternalPath($path))) {
			throw new Exception($this->getInternalPath($path)." already exists, but is not a directory");
		}
	}
	
	function createText($path, $text): string {
		$this->createDir(dirname($path));
		#$dirname = dirname($path);
		#if(!file_exists($dirname)) {
		#	mkdir($this->path."/".$dirname, 0700, true);
		#}
		file_put_contents($this->getInternalPath($path), $text);
		clearstatcache();
	return $this->getInternalPath($path);
	}
	
	function createRandom($path, int $size, int $blocksize=1024): string {
		$this->createDir(dirname($path));
		exec("dd if=/dev/urandom of=".escapeshellarg($this->getInternalPath($path))." bs=".$blocksize." count=".$size." 2> /dev/zero");
		clearstatcache();
	return $this->getInternalPath($path);
	}
	
	function deleteFile($path) {
		if(!file_exists($this->getInternalPath($path))) {
			return;
		}
		if(is_dir($this->getInternalPath($path))) {
			$this->deleteRecurse($this->getInternalPath($path));
			clearstatcache();
			return;
		}
		unlink($this->getInternalPath($path));
		clearstatcache();
	}
}
