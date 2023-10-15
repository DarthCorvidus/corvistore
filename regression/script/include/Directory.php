<?php
namespace Regression;
class Directory {
	private $files;
	private $path;
	function __construct(string $path) {
		$this->recurse($path, "/");
		$this->path = $path;
		#print_r($this->files);
	}
	
	private function recurse(string $path, $prefix) {
		$dh = opendir($path);
		while($file = readdir($dh)) {
			if($file == "." or $file == "..") {
				continue;
			}
			$filepath = $path.$file;
			if(is_link($filepath)) {
				continue;
			}
			if(is_dir($filepath)) {
				#echo $filepath.PHP_EOL;
				$this->recurse($filepath."/", $prefix.$file);
				continue;
			}
			
			#echo $file.PHP_EOL;
			$this->files[$prefix."/".$file] = new DirEntry($path.$file);
		}
		closedir($dh);
	}
	
	function checkEqual(Directory $directory) {
		$ret = TRUE;
		echo "Comparing ".$this->path." vs ".$directory->path.PHP_EOL;
		#print_r($this->files);
		foreach($this->files as $key => $value) {
			if(!isset($directory->files[$key])) {
				echo "\t".$key." does not exist other directory.".PHP_EOL;
				$ret = FALSE;
				continue;
			}
			$ours = $this->files[$key];
			$theirs = $directory->files[$key];
			if($ours->getChecksum()!=$theirs->getChecksum()) {
				echo "\t".$key." checksum not equal.".PHP_EOL;
				$ret = FALSE;
			}
			if($ours->getMode()!=$theirs->getMode()) {
				echo "\t".$key." mode not equal.".PHP_EOL;
				$ret = FALSE;
			}
		}
	return $ret;
	}
}
