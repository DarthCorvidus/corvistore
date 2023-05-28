<?php
class Restore {
	private $node;
	private $pdo;
	private $argv;
	private $target;
	private $storage;
	private $restored = 0;
	private $ignored = 0;
	private $catalog;
	function __construct(EPDO $pdo, Client\Config $config, array $argv) {
		$this->pdo = $pdo;
		$this->node = Node::fromName($this->pdo, $config->getNode());
		$this->argv = new ArgvRestore($argv);
		$this->target = $this->argv->getTargetPath();
		$this->catalog = new Catalog($this->pdo, $this->node);
		/*
		 * This won't work in the long run, if data can migrate to another partition/storage.
		 */
		$this->storage = Storage::fromId($this->pdo, $this->node->getPolicy()->getPartition()->getStorageId());
	}

	function recurseCatalog(string $path, int $depth, CatalogEntry $parent = NULL) {
		$entries = $this->catalog->getEntries($parent);
		$directories = array();
		$files = array();
		for($i=0;$i<$entries->getCount();$i++) {
			$entry = $entries->getEntry($i);
			$latest = $entry->getVersions()->getLatest();
			if($latest->getType()==Catalog::TYPE_DIR) {
				$directories[] = $entry;
			}
			if($latest->getType()==Catalog::TYPE_FILE) {
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

	private function restoreDirectory($path, CatalogEntry $entry) {
		if($this->target=="") {
			return;
		}
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
	
	private function restoreFile($path, CatalogEntry $entry) {
		if($this->target=="") {
			return;
		}
		$version = $entry->getVersions()->getLatest();
		$filepath = $this->target.$path.$entry->getName();
		if(!file_exists($filepath)) {
			echo "Restore file to ".$filepath.PHP_EOL;
			$this->storage->restore($version, $filepath);
			chown($filepath, $version->getOwner());
			chgrp($filepath, $version->getGroup());
			touch($filepath, $version->getMtime());
			#echo $filepath." missing, would restored".PHP_EOL;
			$this->restored++;
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
			$entry = $this->catalog->getEntryByPath($this->node, $this->argv->getRestorePath());
			$this->recurseCatalog($this->argv->getRestorePath()."/", 0, $entry);
		}
		echo "Restored: ".$this->restored.PHP_EOL;
		echo "Ignored:  ".$this->ignored.PHP_EOL;
	}
}
