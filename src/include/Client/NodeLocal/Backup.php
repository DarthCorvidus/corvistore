<?php
class Backup {
	private $pdo;
	private $config;
	private $argv;
	private $inex;
	private $directories;
	private $files;
	private $node;
	private $partition;
	private $storage;
	private $transferred = 0;
	private $catalog;
	private $processed;
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	function __construct(EPDO $pdo, Client\Config $config, array $argv) {
		$this->pdo = $pdo;
		$this->config = $config;
		$this->argv = $argv;
		$this->inex = $config->getInEx();
		#$this->inex = new InEx();
		#$this->inex->addInclude("/boot/");
		#$this->inex->addInclude("/tmp/");
		#$this->inex->addInclude("/var/log/");
		#$this->inex->addInclude("/home/hm/kernel/");
		$this->node = Node::fromName($this->pdo, $this->config->getNode());
		$this->partition = $this->node->getPolicy()->getPartition();
		$this->storage = Storage::fromId($this->pdo, $this->partition->getStorageId());
		$this->catalog = new Catalog($pdo, $this->node);
	}
	
		private function fileowner($filename) {
		$owner = posix_getpwuid(fileowner($filename));
	return $owner["name"];
	}
	
	private function filegroup($filename) {
		$group = posix_getgrgid(filegroup($filename));
	return $group["name"];
	}
	
	private function addVersion(SourceObject $obj, CatalogEntry $entry): VersionEntry {
		if($obj->getType()== Catalog::TYPE_DIR) {
			return $this->addVersionDir($obj, $entry);
		}
		if($obj->getType()== Catalog::TYPE_FILE) {
			return $this->addVersionFile($obj, $entry);
		}
	}
	/*
	 * Time stamps and sizes for directories change constantly, and there is
	 * no need to keep track of them in distinct versions, at least as long
	 * permissions and ownership - which are ignored in the prototype by now -
	 * don't change.
	 */
	private function addVersionDir(SourceObject $obj, CatalogEntry $entry): VersionEntry {
		$param[] = $entry->getId();
		/*
		 * Gets the first result - as we want to get the latest, we have to use
		 * descending order here.
		 */
		$row = $this->pdo->row("select * from d_version where dc_id = ? order by dvs_created_epoch desc limit 1", $param);
		$size = $obj->getSize();
		$mtime = $obj->getMTime();
		if(empty($row) or $row["dvs_type"]!=self::TYPE_DIR) {
			echo "Creating version for directory ".$obj->getPath().PHP_EOL;
			$create["dc_id"] = $entry->getId();
			#$create["dvs_size"] = $size;
			#$create["dvs_mtime"] = $mtime;
			/*
			 * The race condition here - mktime could be 1 s ahead of date - is
			 * negligible ;-).
			 */
			$create["dvs_created_local"] = date("Y-m-d H:i:sP");
			$create["dvs_created_epoch"] = mktime();
			$create["dvs_type"] = self::TYPE_DIR;
			$create["dvs_permissions"] = $obj->getPerms();
			$create["dvs_owner"] = $obj->getOwner();
			$create["dvs_group"] = $obj->getGroup();
			$create["dvs_id"] = $this->pdo->create("d_version", $create);
			$create["dvs_stored"] = 1;
			return VersionEntry::fromArray($create);
		}
		return VersionEntry::fromArray($row);
		#if($row["dvs_size"]!=$size or $row["dvs_mtime"]!=$mtime) {
		#	echo "Updating version of directory ".$path.PHP_EOL;
		#	$update["dvs_size"] = $size;
		#	$update["dvs_mtime"] = $mtime;
		#	$this->pdo->update("d_version", $update, array("dvs_id"=>$row["dvs_id"]));
		#}
	}
	
	private function addVersionFile(SourceObject $object, CatalogEntry $entry): VersionEntry {
		$version["dc_id"] = $entry->getId();
		$version["dvs_size"] = $object->getSize();
		$version["dvs_mtime"] = $object->getMTime();
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = mktime();
		$version["dvs_type"] = self::TYPE_FILE;
		$version["dvs_permissions"] = $object->getPerms();
		$version["dvs_owner"] = $object->getOwner();
		$version["dvs_group"] = $object->getGroup();
		$version["dvs_stored"] = 0;
		
		$param[] = $version["dc_id"];
		$param[] = $version["dvs_size"];
		$param[] = $version["dvs_mtime"];
		$param[] = $version["dvs_type"];
		$param[] = 1;
		$row = $this->pdo->row("select * from d_version where dc_id = ? and dvs_size = ? and dvs_mtime = ? and dvs_type = ? and dvs_stored = ? order by dvs_created_epoch desc limit 1", $param);
		if(empty($row)) {
			echo "Creating version for file ".$object->getPath().PHP_EOL;
			$version["dvs_id"] = $this->pdo->create("d_version", $version);
		return VersionEntry::fromArray($version);
		}
		
		if($version["dvs_permissions"]!=$row["dvs_permissions"] or $version["dvs_owner"]!=$row["dvs_owner"] or $version["dvs_group"]!=$row["dvs_group"]) {
			echo "Updating metadata for file ".$object->getPath().PHP_EOL;
			$update["dvs_permissions"] = $version["dvs_permissions"];
			$update["dvs_owner"] = $version["dvs_owner"];
			$update["dvs_group"] = $version["dvs_group"];
			$this->pdo->update("d_version", $update, array("dvs_id"=>$row["dvs_id"]));
		}
	return VersionEntry::fromArray($row);
	}
	
	/*
	 * We need some kind of deleted entry; consider you ran a backup on
	 * 2023-01-01, then deleted a file on 2023-01-02, made another backup on
	 * 2023-01-03 and then finally do a restore of yesterday on 2023-01-04, the
	 * restore needs to know that it should not restore the file you deleted on
	 * 2023-01-02.
	 */
	private function addDeleted($id) {
		$version["dvs_type"] = 0;
		$version["dc_id"] = $id;
		$version["dvs_size"] = 0;
		$version["dvs_mtime"] = 0;
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = mktime();
		$param[] = $version["dc_id"];
		$param[] = $version["dvs_size"];
		$param[] = $version["dvs_mtime"];
		$param[] = $version["dvs_type"];
		$row = $this->pdo->row("select * from d_version where dc_id = ? and dvs_size = ? and dvs_mtime = ? and dvs_type = ? order by dvs_created_epoch desc limit 1", $param);
		if(!empty($row)) {
			return;
		}
		echo "Flagging catalog entry ".$id." as deleted".PHP_EOL;
		$this->pdo->create("d_version", $version);
	}
	
	#private function getCatalogEntry(SourceObject $obj, CatalogEntry $parent = NULL): CatalogEntry  {
	#	$entry = CatalogEntry::create($this->pdo, $obj, $parent);
	#	$this->addVersion($obj, $entry, $parent);
	#return $entry;
	#}
	
	private function recurseFiles(string $path, $depth, CatalogEntry $parent = NULL) {
		$files = new Files();
		$directories = array();
		$all = array();
		
		foreach(glob($path."/{,.}*", GLOB_BRACE) as $value) {
			if(in_array(basename($value), array(".", ".."))) {
				continue;
			}
			
			if(is_link($value)) {
				continue;
			}
			#if(in_array($value, $this->exclude)) {
			#	continue;
			#}
			if(is_dir($value) and ($this->inex->isValid($value) or $this->inex->transitOnly($value))) {
				$this->processed++;
				if($this->processed%5000==0) {
					echo "Processed ".$this->processed." files.".PHP_EOL;
				}
				$file = new File($value);
				$files->addEntry($file);
				continue;
			}
			if(is_file($value) and $this->inex->isValid($path)) {
				$this->processed++;
				if($this->processed%5000==0) {
					echo "Processed ".$this->processed." files.".PHP_EOL;
				}
				$file = new File($value);
				$files->addEntry($file);
				continue;
			}
		}

		$this->pdo->beginTransaction();
		$catalogEntries = $this->catalog->getEntries($parent);
		$diff = $catalogEntries->getDiff($files);
		// Add new files (did not exist before).
		for($i=0;$i<$diff->getNew()->getCount();$i++) {
			$file = $diff->getNew()->getEntry($i);
			echo "Creating ".$file->getPath().PHP_EOL;
			$entry = $this->catalog->newEntry($file, $parent);
			$catalogEntries->addEntry($entry);
			if($entry->getVersions()->getLatest()->getType()==Catalog::TYPE_FILE) {
				$this->storage->store($entry->getVersions()->getLatest(), $this->partition, $file);
			}
			
			
		}
		// Add changed files.
		for($i=0;$i<$diff->getChanged()->getCount();$i++) {
			$file = $diff->getChanged()->getEntry($i);
			echo "Updating ".$file->getPath().PHP_EOL;
			$entry = $this->catalog->updateEntry($catalogEntries->getByName($file->getBasename()), $file);
			$this->storage->store($entry->getVersions()->getLatest(), $this->partition, $file);
		}
		
		// Mark files as deleted.
		for($i=0;$i<$diff->getDeleted()->getCount();$i++) {
			$catalogEntry = $diff->getDeleted()->getEntry($i);
			echo "Deleting ".$catalogEntry->getName().PHP_EOL;
			$this->catalog->deleteEntry($catalogEntry);
		}
		
		
		
		$this->pdo->commit();
		
		$directories = $files->getDirectories();
		for($i=0;$i<$directories->getCount();$i++) {
			$dir = $directories->getEntry($i);
			$parent = $catalogEntries->getByName($dir->getBasename());
			$this->recurseFiles($dir->getPath(), $depth, $parent);
		}
	}
	
		
	function run() {
		$start = hrtime();
		$this->recurseFiles("/", 0);
		$end = hrtime();
		$elapsed = $end[0]-$start[0];
		echo "Processed:    ".number_format($this->processed).PHP_EOL;
		echo "Directories:  ".$this->directories.PHP_EOL;
		echo "Files:        ".$this->files.PHP_EOL;
		echo "Transferred:  ".number_format($this->transferred, 0).PHP_EOL;
		$stored = $this->pdo->result("select sum(dvs_size) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ? AND dvs_stored = ?", array($this->node->getId(), 1));
		echo "Occupancy:    ".number_format($stored, 0).PHP_EOL;
		$timeconvert = new ConvertTime(ConvertTime::SECONDS, ConvertTime::HMS);
		echo "Elapsed time: ".$timeconvert->convert($elapsed).PHP_EOL;
		
	}
}
