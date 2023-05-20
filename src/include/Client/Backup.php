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
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	function __construct(EPDO $pdo, Client\Config $config, array $argv) {
		$this->pdo = $pdo;
		$this->config = $config;
		$this->argv = $argv;
		$this->inex = $config->getInEx();
		$this->node = Node::fromName($this->pdo, $this->config->getNode());
		$this->partition = $this->node->getPolicy()->getPartition();
		$this->storage = Storage::fromId($this->pdo, $this->partition->getStorageId());
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
		$files = array();
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
			$all[] = basename($value);
			if(is_dir($value) and ($this->inex->isValid($value) or $this->inex->transitOnly($value))) {
				$directories[] = $value;
				continue;
			}
			if(is_file($value) and $this->inex->isValid($path)) {
				$files[] = $value;
				continue;
			}
			
		}
		$directoriesCreated = array();
		$this->pdo->beginTransaction();
		foreach($directories as $key => $value) {
			$this->directories++;
			$source = new SourceObject($this->node, $value);
			$entry = CatalogEntry::create($this->pdo, $source, $parent);
			$version = $this->addVersion($source, $entry, $parent);
			$directoriesCreated[$value] = $entry;
			#echo str_repeat(" ", $depth)."+ [".$id."] ".basename($value).PHP_EOL;
			
		}
		$this->pdo->commit();
		
		foreach($directoriesCreated as $key => $value) {
			$this->recurseFiles($key, $depth+1, $value);
		}
		
		
		$this->pdo->beginTransaction();
		foreach($files as $key => $value) {
			$this->files++;
			if($this->files % 1000==0) {
				echo "------------".$this->files." processed------------".PHP_EOL;
			}
			$obj = new SourceObject($this->node, $value);
			$entry = CatalogEntry::create($this->pdo, $obj, $parent);
			$version = $this->addVersion($obj, $entry, $parent);
			if(!$version->isStored()) {
				$this->storage->store($version, $this->partition, $obj);
				$this->transferred += $obj->getSize();
				$this->pdo->commit();
				$this->pdo->beginTransaction();
				$version->setStored($this->pdo);
			}
			#echo str_repeat(" ", $depth)." [".$fileId."] ".basename($value).PHP_EOL;
		}
		$this->pdo->commit();
		
		if($parent==NULL) {
			$stmt = $this->pdo->prepare("select dc_id, dc_name from d_catalog where dc_parent IS NULL");
			$stmt->execute();
		} else {
			$stmt = $this->pdo->prepare("select dc_id, dc_name from d_catalog where dc_parent = ?");
			$stmt->execute(array($parent->getId()));
		}
		foreach($stmt as $key => $value) {
			if(!in_array($value["dc_name"], $all)) {
				$this->addDeleted($value["dc_id"]);
			}
		}
		#print_r($directories);
		#print_r($files);
	}

	
	function run() {
		$start = hrtime();
		$this->recurseFiles("/", 0);
		$end = hrtime();
		$elapsed = $end[0]-$start[0];
		echo "Directories:  ".$this->directories.PHP_EOL;
		echo "Files:        ".$this->files.PHP_EOL;
		echo "Transferred:  ".number_format($this->transferred, 0).PHP_EOL;
		$stored = $this->pdo->result("select sum(dvs_size) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ? AND dvs_stored = ?", array($this->node->getId(), 1));
		echo "Occupancy:    ".number_format($stored, 0).PHP_EOL;
		$timeconvert = new ConvertTime(ConvertTime::SECONDS, ConvertTime::HMS);
		echo "Elapsed time: ".$timeconvert->convert($elapsed).PHP_EOL;
	}
}
