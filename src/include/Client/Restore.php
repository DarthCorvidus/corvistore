<?php
class Restore {
	private $node;
	private $pdo;
	private $argv;
	private $target;
	private $storage;
	private $restored = 0;
	private $ignored = 0;
	function __construct(EPDO $pdo, Client\Config $config, array $argv) {
		$this->pdo = $pdo;
		$this->node = Node::fromName($this->pdo, $config->getNode());
		$this->argv = new ArgvRestore($argv);
		$this->target = $this->argv->getTargetPath();
		/*
		 * This won't work in the long run, if data can migrate to another partition/storage.
		 */
		$this->storage = Storage::fromId($this->pdo, $this->node->getPolicy()->getPartition()->getStorageId());
	}

	function recurseCatalog(string $path, int $depth, CatalogEntry $parent = NULL) {
		$query = "";
		$param = array();
		$entries = array();
		/*
		 * The task to get the last version of a catalog entry will be done by
		 * PHP, using CatalogEntry::getLatest(). This is not done „by the book“,
		 * which would be to do it in SQL.
		 */
		
		if($parent==NULL) {
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_parent IS NULL ORDER BY dc_id, dvs_created_epoch ASC";
		} else {
			$param[] = $parent->getId();
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_parent = ? ORDER BY dc_id, dvs_created_epoch ASC";
		}
		$stmt = $this->pdo->prepare($query);
		$stmt->execute($param);
		foreach($stmt as $value) {
			if(!isset($entries[$value["dc_id"]])) {
				$entries[$value["dc_id"]] = CatalogEntry::fromArray($this->pdo, $value);
				$version = VersionEntry::fromArray($value);
				$versions = $entries[$value["dc_id"]]->getVersions()->addVersion($version);
			} else {
				$version = VersionEntry::fromArray($value);
				$versions = $entries[$value["dc_id"]]->getVersions()->addVersion($version);
			}
		}
		foreach($entries as $key => $value) {
			$version = $value->getVersions()->getLatest();
			if($version->getType()== Catalog::TYPE_DIR) {
				#echo $path.$value->name."/".PHP_EOL;
				#$this->recurseCatalog($value->id, $depth+1, $path.$value->name."/");
				$this->restoreDirectory($path, $value);
				$this->recurseCatalog($path.$value->getName()."/", $depth+1, $value);
			}
			if($version->getType()== Catalog::TYPE_FILE) {
				$this->restoreFile($path, $value);
			}
		}
	}

	private function restoreDirectory($path, CatalogEntry $entry) {
		if($this->target=="") {
			return;
		}
		$version = $entry->getVersions()->getLatest();
		$filepath = $this->target.$path.$entry->getName();
		if(!file_exists($filepath)) {
			echo $filepath.PHP_EOL;
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
			$catalog = new Catalog($this->pdo);
			$entry = $catalog->getEntryByPath($this->node, $this->argv->getRestorePath());
			$this->recurseCatalog($this->argv->getRestorePath()."/", 0, $entry);
		}
		echo "Restored: ".$this->restored.PHP_EOL;
		echo "Ignored:  ".$this->ignored.PHP_EOL;
	}
}
