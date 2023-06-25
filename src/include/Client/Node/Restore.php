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
	function __construct(\Net\Protocol $protocol, \Client\Config $config, array $argv) {
		$this->config = $config;
		$this->argv = new \ArgvRestore($argv);
		$this->inex = $config->getInEx();
		$this->protocol = $protocol;
		$this->target = $this->argv->getTargetPath();
		/*
		 * As long as the product is in a „pre alpha state“, in-place restores
		 * are disabled, to prevent data loss due to a failed test run.
		 */
		if($this->target=="" or $this->target=="/") {
			echo "in place restore is not yet supported.".PHP_EOL;
			exit();
		}
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
			$latest = $entry->getVersions()->getLatest();
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
		$version = $entry->getVersions()->getLatest();
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
		$version = $entry->getVersions()->getLatest();
		$filepath = $this->target.$path.$entry->getName();
		if(!file_exists($filepath)) {
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
		$mtime = filemtime($filepath);
		if($mtime==$version->getMtime()) {
			$this->ignored++;
			#echo $filepath." is equal, would ignore.".PHP_EOL;
		}

		if($mtime>$version->getMtime()) {
			$this->ignored++;
			#echo $filepath." is newer, would ignore.".PHP_EOL;
		}

		if(filemtime($filepath)<$version->getMtime()) {
			$this->ignored++;
			#echo $filepath." is older, would prompt.".PHP_EOL;
		}

	}
	
	function run() {
		echo $this->argv->getRestorePath().PHP_EOL;
		if($this->argv->getRestorePath()=="/") {
			$this->recurseCatalog("/", 0);
		} else {
			$this->protocol->sendCommand("GET PATH ".$this->argv->getRestorePath());
			$entry = $this->protocol->getUnserializePHP();
			$this->recurseCatalog($this->argv->getRestorePath()."/", 0, $entry);
		}
		$this->protocol->sendCommand("QUIT");
		echo "Restored:    ".$this->restored.PHP_EOL;
		echo "Ignored:     ".$this->ignored.PHP_EOL;
		echo "Transferred: ".number_format($this->size)." Bytes".PHP_EOL;
	}
}
