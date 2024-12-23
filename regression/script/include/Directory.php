<?php
namespace Regression;
class Directory {
	private $files;
	private $path;
	private int $countMissingFiles = 0;
	private int $countDifferentFiles = 0;
	private int $countDifferentModes = 0;
	private int $countDifferentCTime = 0;
	private int $countDifferentType = 0;
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
				#echo "\t".$key." does not exist other directory.".PHP_EOL;
				$this->countMissingFiles++;
				$ret = FALSE;
				continue;
			}
			$ours = $this->files[$key];
			$theirs = $directory->files[$key];
			if($ours->getFile()->getType() !== $theirs->getFile()->getType()) {
				$this->countDifferentType++;
			// It is pointless to check for other differences if type is different.
			continue;
			}
			
			if($ours->getChecksum()!=$theirs->getChecksum()) {
				#echo "\t".$key." checksum not equal.".PHP_EOL;
				$this->countDifferentFiles++;
				$ret = FALSE;
			}
			if($ours->getMode()!=$theirs->getMode()) {
				#echo "\t".$key." mode not equal.".PHP_EOL;
				$this->countDifferentModes++;
				$ret = FALSE;
			}
			
			if($ours->getFile()->getType() === \Catalog::TYPE_LINK) {
				continue;
			}
			
			if($ours->getMtime()!=$theirs->getMtime()) {
				echo "Different ctime for ".$key.PHP_EOL;
				$this->countDifferentCTime++;
				$ret = FALSE;
			}
		}
		echo "Compared ".$this->path." vs ".$directory->path.":".PHP_EOL;
		echo "\tMissing file: ".$this->countMissingFiles.PHP_EOL;
		echo "\tDifferent file: ".$this->countDifferentFiles.PHP_EOL;
		echo "\tDifferent mode: ".$this->countDifferentModes.PHP_EOL;
		echo "\tDifferent ctime: ".$this->countDifferentCTime.PHP_EOL;
		echo "\tDifferent type: ".$this->countDifferentType.PHP_EOL;
	return $ret;
	}
}
