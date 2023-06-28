<?php
namespace Node;
/*
 * Restore from the server as a client. 
 */
class Restore {
	private $argv;
	private $target;
	private $restored = 0;
	private $ignored = 0;
	private $size;
	private $protocol;
	private $timestamp;
	private $replaceOlder = NULL;
	private $replaceEqual = NULL;
	private $replaceNewer = NULL;
	private $replaceSize = NULL;
	function __construct(\Net\Protocol $protocol, \Client\Config $config, array $argv) {
		$this->config = $config;
		$this->argv = new \ArgvRestore($argv);
		$this->inex = $config->getInEx();
		$this->protocol = $protocol;
		$this->target = $this->argv->getTargetPath();
		$this->timestamp = strtotime($this->argv->getTimestamp());
		/*
		 * As long as the product is in a „pre alpha state“, in-place restores
		 * are disabled, to prevent data loss due to a failed test run.
		 */
		if($this->target=="" or $this->target=="/") {
			echo "in place restore is not yet supported.".PHP_EOL;
			exit();
		}
	}
	
	private function queryReplace(&$keep, string $filepath, string $status) {
		if($keep != NULL) {
			return $keep;
		}
		echo "File ".$filepath." exists and is ".$status.". Action:".PHP_EOL;
		while(true) {
			echo "[r]eplace once".PHP_EOL;
			echo "[R]eplace always".PHP_EOL;
			echo "[s]kip (or enter)".PHP_EOL;
			echo "[S]kip always".PHP_EOL;
			echo "[c]ancel".PHP_EOL;
			echo "> ";
			$input = trim(fgets(STDIN));
			if($input=="S") {
				$keep = "s";
			return "s";
			}
			if($input=="R") {
				$keep = "r";
			return "r";
			}
			if($input=="c") {
				$this->displaySummary();
				$this->protocol->sendCommand("QUIT");
				exit(0);
			}
			if(in_array($input, array("c", "s", "r", "S", "R"))) {
				return $input;
			}
		}
	}
	
	function queryReplaceOlder($filepath) {
		$input = $this->queryReplace($this->replaceOlder, $filepath, "older");
	return $input;
	}

	function queryReplaceEqual($filepath) {
		$input = $this->queryReplace($this->replaceEqual, $filepath, "equal");
	return $input;
	}
	
	function queryReplaceNewer($filepath) {
		$input = $this->queryReplace($this->replaceNewer, $filepath, "newer");
	return $input;
	}

	function recurseCatalog(string $path, int $depth, \CatalogEntry $parent = NULL) {
		if($parent!=NULL) {
			#$parent->getName().PHP_EOL;
		}
		#$entries = $this->catalog->getEntries($parent);
		if($parent!=NULL) {
			$this->protocol->sendCommand("GET CATALOG ".$parent->getId());
		} else {
			$this->protocol->sendCommand("GET CATALOG 0");
		}
		
		$entries = $this->protocol->getUnserializePHP();
		#$entry
		$directories = array();
		$files = array();
		for($i=0;$i<$entries->getCount();$i++) {
			$entry = $entries->getEntry($i);
			// get Versions filtered by Timestamp.
			$versions = $entry->getVersions()->filterToTimestamp($this->timestamp);
			// If there are no versions for a given timestamp, abort.
			if($versions->getCount()==0) {
				continue;
			}
			$latest = $versions->getLatest();
			if($latest->getType()==\Catalog::TYPE_DIR) {
				$directories[] = $entry;
			}
			if($latest->getType()==\Catalog::TYPE_FILE) {
				$files[] = $entry;
			}
		}
		foreach($directories as $value) {
			$this->restoreDirectory($path, $value);
		}
		foreach($files as $value) {
			$this->restoreFile($path, $value);
		}
		foreach($directories as $value) {
			$this->recurseCatalog($path.$value->getName()."/", $depth+1, $value);
		}
	}

	private function restoreDirectory($path, \CatalogEntry $entry) {
		# We have to filter again.
		$version = $entry->getVersions()->filterToTimestamp($this->timestamp)->getLatest();
		$filepath = $this->target.$path.$entry->getName();
		if(!file_exists($filepath)) {
			echo "Restoring directory ".$filepath.PHP_EOL;
			mkdir($filepath, $version->getPermissions(), true);
			chown($filepath, $version->getOwner());
			chgrp($filepath, $version->getGroup());
			return;
		}
	}
	
	private function restoreFile($path, \CatalogEntry $entry) {
		# We have to filter again.
		$version = $entry->getVersions()->filterToTimestamp($this->timestamp)->getLatest();
		$filepath = $this->target.$path.$entry->getName();
		if(file_exists($filepath)) {
			if(filemtime($filepath)<$version->getMtime() && $this->queryReplaceOlder($filepath)=="s") {
				$this->ignored++;
				return;
			}

			if(filemtime($filepath)==$version->getMtime() && $this->queryReplaceEqual($filepath)=="s") {
				$this->ignored++;
				return;
			}

			if(filemtime($filepath)>$version->getMtime() && $this->queryReplaceNewer($filepath)=="s") {
				$this->ignored++;
				return;
			}
			
		}
		echo "Restore file to ".$filepath.PHP_EOL;
		$this->protocol->sendCommand("GET VERSION ".$version->getId());
		/*
		 * I opted against having Restore implement TransferListener; 
		 * I prefer to be sure to get a clean slate on each restore.
		 */
		$restoreListener = new RestoreListener($filepath);
		$this->protocol->getRaw($restoreListener);
		chown($filepath, $version->getOwner());
		chgrp($filepath, $version->getGroup());
		touch($filepath, $version->getMtime());
		##echo $filepath." missing, would restored".PHP_EOL;
		$this->restored++;
		$this->size += $version->getSize();
	return;
	}
	
	private function restoreHierarchy() {
		
	}

	private function restoreParents() {
		$exp = explode("/", $this->argv->getRestorePath());
		$i = 0;
		$parent = 0;
		$parentpath = "";
		foreach($exp as $key => $value) {
			if($value==NULL) {
				continue;
			}
			$this->protocol->sendCommand("GET CATALOG ".$parent);
			$entries = $this->protocol->getUnserializePHP();
			$entry = $entries->getByName($value);
			$versions = $entry->getVersions()->filterToTimestamp($this->timestamp);
			if($versions->getLatest()->getType()== \Catalog::TYPE_DIR) {
				#echo $parentpath.PHP_EOL;
				#echo $entry->getName().PHP_EOL;
				$this->restoreDirectory($parentpath."/", $entry);
				$parent = $entry->getId();
				$parententry = $entry;
				$parentpath .= "/".$entry->getName();
			}
		}
	return $parentpath;
	}
	
	private function displaySummary() {
		echo "Restored:    ".$this->restored.PHP_EOL;
		echo "Ignored:     ".$this->ignored.PHP_EOL;
		echo "Transferred: ".number_format($this->size)." Bytes".PHP_EOL;
	}
	
	function run() {
		#echo $this->argv->getRestorePath().PHP_EOL;
		if($this->argv->getRestorePath()=="/") {
			$this->recurseCatalog("/", 0);
		} else {
			/**
			 * When a restore path is deeper below root, the path leading to the
			 * part to be restored has to be created as well. This could of
			 * course just be done by mkdir, but then the path would have
			 * root.root as path. Therefore, Restore::restoreParents will be
			 * used to follow the restore path to the point where a restore is
			 * wished.
			 */
			// Get requested path/backup.
			$this->protocol->sendCommand("GET PATH ".$this->argv->getRestorePath());
			$targetEntry = $this->protocol->getUnserializePHP();
			// quit with error message if no version exists on or before timestamp.
			$targetVersions = $targetEntry->getVersions()->filterToTimestamp($this->timestamp);
			if($targetVersions->getCount()==0) {
				$this->protocol->sendCommand("QUIT");
				throw new \Exception($this->argv->getRestorePath()." does not exist in backup before ".date("Y-m-d H:i:s", $this->timestamp));
			}
			// restore all paths leading to the desired target
			$parentpath = $this->restoreParents();
			// if the target is a file, restore single file and quit.
			if($targetVersions->getLatest()->getType()== \Catalog::TYPE_FILE) {
				$this->restoreFile($parentpath."/", $targetEntry);
				$this->displaySummary();
				$this->protocol->sendCommand("QUIT");
				return;
			}
			// if target is a folder, recurse folder
			$this->recurseCatalog($parentpath."/", 0, $targetEntry);
		}
		$this->displaySummary();
		$this->protocol->sendCommand("QUIT");
	}
}
