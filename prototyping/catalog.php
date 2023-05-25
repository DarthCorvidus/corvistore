#!/usr/bin/php
<?php
require_once __DIR__."/../vendor/autoload.php";
if(!isset($argv[1])) {
	echo "No mode, please use backup or restore.".PHP_EOL;
	exit();
}

if(!in_array($argv[1], array("backup", "restore"))) {
	echo "Invalid mode, please use backup or restore.".PHP_EOL;
	exit();
}

class CatalogEntry {
	public $id;
	public $name;
	public $parent;
	private $versions;
	function __construct(array $array) {
		$this->id = $array["dc_id"];
		$this->name = $array["dc_name"];
		$this->parent = $array["dc_parent"];
		$this->addVersion($array);
	}
	
	static function fromName(EPDO $pdo, string $name, CatalogEntry $parent = NULL): CatalogEntry {
		$param = array();
		$param[] = $name;
		$query = "";
		if($parent==NULL) {
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_name = ? and dc_parent IS NULL ORDER BY dc_id, dvs_created_epoch DESC";
		} else {
			$param[] = $parent->id;
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_name = ? and dc_parent = ? ORDER BY dc_id, dvs_created_epoch DESC";
		}
		$stmt = $pdo->prepare($query);
		$stmt->execute($param);
		foreach($stmt as $key => $value) {
			if($key == 0) {
				$entry = new CatalogEntry($value);
				$entry->addVersion($value);
				continue;
			}
			$entry->addVersion($value);
		}
	return $entry;
	}
	
	function addVersion(array $array) {
		$this->versions[] = new VersionEntry($array);
	}
	
	function getLatest(): VersionEntry {
		return $this->versions[count($this->versions)-1];
	}

}

class VersionEntry {
	public $id;
	public $catalogId;
	public $size;
	public $mtime;
	public $createdAt;
	public $type;
	function __construct(array $array) {
		$this->id = $array["dvs_id"];
		$this->catalogId = $array["dc_id"];
		$this->size = $array["dvs_size"];
		$this->mtime = $array["dvs_mtime"];
		$this->createdAt = $array["dvs_created_epoch"];
		$this->type = $array["dvs_type"];
		$this->owner = $array["dvs_owner"];
		$this->group = $array["dvs_group"];
		$this->permissions = $array["dvs_permissions"];
	}
}

class Recurse {
	private $exclude;
	private $pdo;
	private $inex;
	const TYPE_MISS = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	private $argv;
	private $directories = 0;
	private $files = 0;
	private $processed = 0;
	private $created = 0;
	private $process = array();
	private $origSize = 0;
	private $resExamined = 0;
	private $resRestored = 0;
	private $resEqual = 0;
	private $resOlder = 0;
	private $resNewer = 0;
	private $parents = array();
	function __construct(array $argv) {
		$this->argv = $argv;
		$shared = new Shared();
		if(!file_exists(__DIR__."/catalog.sqlite")) {
			throw new Exception("Please create catalog.sqlite first, using default-catalog.sql");
		}
		$shared->useSQLite(__DIR__."/catalog.sqlite");
		$this->origSize = filesize(__DIR__."/catalog.sqlite");
		$this->pdo = $shared->getEPDO();
		$this->inex = new InEx();
		$this->inex->addExclude("/proc/");
		$this->inex->addExclude("/run/");
		$this->inex->addExclude("/dev/");
		$this->inex->addExclude("/sys/");
		$this->inex->addExclude("/virtual/");
		$this->inex->addExclude("/home/hm/backup");
	}
	
	private function fileowner($filename) {
		$owner = posix_getpwuid(fileowner($filename));
	return $owner["name"];
	}
	
	private function filegroup($filename) {
		$group = posix_getgrgid(filegroup($filename));
	return $group["name"];
	}
	
	private function addVersion($path, $id) {
		if(is_dir($path)) {
			$this->addVersionDir($path, $id);
		}
		if(is_file($path)) {
			$this->addVersionFile($path, $id);
		}
	}
	/*
	 * Time stamps and sizes for directories change constantly, and there is
	 * no need to keep track of them in distinct versions, at least as long
	 * permissions and ownership - which are ignored in the prototype by now -
	 * don't change.
	 */
	private function addVersionDir($path, $id) {
		$param[] = $id;
		/*
		 * Gets the first result - as we want to get the latest, we have to use
		 * descending order here.
		 */
		$row = $this->pdo->row("select * from d_version where dc_id = ? order by dvs_created_epoch desc limit 1", $param);
		$size = filesize($path);
		$mtime = filemtime($path);
		if(empty($row) or $row["dvs_type"]!=self::TYPE_DIR) {
			echo "Creating version for directory ".$path.PHP_EOL;
			$create["dc_id"] = $id;
			#$create["dvs_size"] = $size;
			#$create["dvs_mtime"] = $mtime;
			/*
			 * The race condition here - mktime could be 1 s ahead of date - is
			 * negligible ;-).
			 */
			$create["dvs_created_local"] = date("Y-m-d H:i:sP");
			$create["dvs_created_epoch"] = mktime();
			$create["dvs_type"] = self::TYPE_DIR;
			$create["dvs_permissions"] = fileperms($path);
			$create["dvs_owner"] = $this->fileowner($path);
			$create["dvs_group"] = $this->filegroup($path);
			$this->pdo->create("d_version", $create);
			return;
		}
		#if($row["dvs_size"]!=$size or $row["dvs_mtime"]!=$mtime) {
		#	echo "Updating version of directory ".$path.PHP_EOL;
		#	$update["dvs_size"] = $size;
		#	$update["dvs_mtime"] = $mtime;
		#	$this->pdo->update("d_version", $update, array("dvs_id"=>$row["dvs_id"]));
		#}
	}
	
	private function addVersionFile($path, $id) {
		$version["dc_id"] = $id;
		$version["dvs_size"] = filesize($path);
		$version["dvs_mtime"] = filemtime($path);
		$version["dvs_created_local"] = date("Y-m-d H:i:sP");
		$version["dvs_created_epoch"] = mktime();
		$version["dvs_type"] = self::TYPE_FILE;
		$version["dvs_permissions"] = fileperms($path);
		$version["dvs_owner"] = $this->fileowner($path);
		$version["dvs_group"] = $this->filegroup($path);
		
		$param[] = $version["dc_id"];
		$param[] = $version["dvs_size"];
		$param[] = $version["dvs_mtime"];
		$param[] = $version["dvs_type"];
		$row = $this->pdo->row("select * from d_version where dc_id = ? and dvs_size = ? and dvs_mtime = ? and dvs_type = ? order by dvs_created_epoch desc limit 1", $param);
		if(empty($row)) {
			echo "Creating version for file ".$path.PHP_EOL;
			$this->pdo->create("d_version", $version);
		return;
		}
		
		#if($version["dvs_permissions"]!=$row["dvs_permissions"] or $version["dvs_owner"]!=$row["dvs_owner"] or $version["dvs_group"]!=$row["dvs_group"]) {
		#	echo "Updating metadata for file ".$path.PHP_EOL;
		#	$update["dvs_permissions"] = $version["dvs_permissions"];
		#	$update["dvs_owner"] = $version["dvs_owner"];
		#	$update["dvs_group"] = $version["dvs_group"];
		#	$this->pdo->update("d_version", $update, array("dvs_id"=>$id));
		#}
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
	
	private function getFileId($path, $parentid = NULL)  {
		$this->processed++;
		if($this->processed%5000==0) {
			echo "Processed ".number_format($this->processed)." entries.".PHP_EOL;
		}
		$name = basename($path);
		$param[] = $name;
		$create["dc_name"] = $name;
		if($parentid == NULL) {
			$sql = "select * from d_catalog where dc_name = ? and dc_parent IS NULL";
		} else {
			$param[] = $parentid;
			$create["dc_parent"] = $parentid;
			$sql = "select * from d_catalog where dc_name = ? and dc_parent = ?";
		}
		#echo $sql.PHP_EOL;
		$row = $this->pdo->row($sql, $param);
		if(empty($row)) {
			$id = $this->pdo->create("d_catalog", $create);
		} else {
			$id = $row["dc_id"];
		}
		$this->addVersion($path, $id);
	return $id;
	}
	
	private function process(string $path) {
		if(count($this->process)<100) {
			$this->process[] = $path;
		return;
		}
		$this->processArray($this->process);
		$this->process = array();
		$this->process[] = $path;
	}
	
	private function processArray(array $process) {
		$this->pdo->beginTransaction();
		foreach($process as $value) {
			if(is_dir($value) && dirname($value)=="/") {
				$id = $this->getFileId($value);
				$this->parents[$value] = $id;
				#print_r($this->parents);
			continue;
			}
			if(is_dir($value)) {
				$id = $this->getFileId($value, $this->parents[dirname($value)]);
				$this->parents[$value] = $id;
			continue;
			}
			if(is_file($value)) {
				$id = $this->getFileId($value, $this->parents[dirname($value)]);
			}
		}
		$this->pdo->commit();
	}

	private function recurseFiles($path, $depth, $parentid = NULL) {
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
				#$this->process($value);
				#$this->recurseFiles($value, $depth+1);
				$directories[] = $value;
				continue;
			}
			if(is_file($value) and $this->inex->isValid($path)) {
				#$this->process($value);
				$files[] = $value;
				continue;
			}
			
		}
		$directoriesCreated = array();
		$this->pdo->beginTransaction();
		foreach($directories as $key => $value) {
			$this->directories++;
			$id = $this->getFileId($value, $parentid);
			$directoriesCreated[$id] = $value;
			#echo str_repeat(" ", $depth)."+ [".$id."] ".basename($value).PHP_EOL;
			
		}
		$this->pdo->commit();
		
		foreach($directoriesCreated as $key => $value) {
			$this->recurseFiles($value, $depth+1, $key);
		}
		
		
		$this->pdo->beginTransaction();
		foreach($files as $key => $value) {
			$this->files++;
			$fileId = $this->getFileId($value, $parentid);
			#echo str_repeat(" ", $depth)." [".$fileId."] ".basename($value).PHP_EOL;
		}
		$this->pdo->commit();
		
		$this->pdo->beginTransaction();
		$stmt = $this->pdo->prepare("select dc_id, dc_name from d_catalog where dc_parent = ?");
		$stmt->execute(array($parentid));
		foreach($stmt as $key => $value) {
			if(!in_array($value["dc_name"], $all)) {
				$this->addDeleted($value["dc_id"]);
			}
		}
		$this->pdo->commit();
		#print_r($directories);
		#print_r($files);
	}
	
	function recurseCatalog($parent = NULL, $depth, $path) {
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
			$param[] = $parent;
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_parent = ? ORDER BY dc_id, dvs_created_epoch ASC";
		}
		$stmt = $this->pdo->prepare($query);
		$stmt->execute($param);
		foreach($stmt as $value) {
			if(!isset($entries[$value["dc_id"]])) {
				$entries[$value["dc_id"]] = new CatalogEntry($value);
			} else {
				$entries[$value["dc_id"]]->addVersion($value);
			}
		}
		foreach($entries as $key => $value) {
			$version = $value->getLatest();
			if($version->type==self::TYPE_DIR) {
				$this->restoreFile($path, $value);
				$this->recurseCatalog($value->id, $depth+1, $path.$value->name."/");
			}
			if($version->type==self::TYPE_FILE) {
				$this->restoreFile($path, $value);
			}

		}
	}
	
	private function restoreFile($path, CatalogEntry $entry) {
		$version = $entry->getLatest();
		$filepath = $path.$entry->name;
		$this->resExamined++;
		if(!file_exists($path.$entry->name)) {
			$this->resRestored++;
			return;
		}
		$mtime = filemtime($filepath);
		if($mtime==$version->mtime) {
			$this->resEqual++;
		}

		if($mtime>$version->mtime) {
			$this->resNewer++;
		}

		if(filemtime($filepath)<$version->mtime) {
			$this->resOlder++;
		}

	}
	
	function run() {
		if($this->argv[1]=="restore") {
			$start = hrtime(true);
			$this->runRestore();
			$time = (hrtime(true)-$start)/1000000000;
			$tc = new ConvertTime(ConvertTime::SECONDS, ConvertTime::HMS);
			echo "Examined: ".number_format($this->resExamined).PHP_EOL;
			echo "Older:    ".number_format($this->resOlder).PHP_EOL;
			echo "Equal:    ".number_format($this->resEqual).PHP_EOL;
			echo "Newer:    ".number_format($this->resNewer).PHP_EOL;
			echo "Missing:  ".number_format($this->resRestored).PHP_EOL;
			echo "Elapsed:  ".$tc->convert($time).PHP_EOL;
			echo "Versions: ".number_format($this->pdo->result("select count(*) from d_version", array())).PHP_EOL;
		}

		if($this->argv[1]=="backup") {
			$this->runBackup();
		}
	}
	
	function runBackup() {
		$start = hrtime(true);
		$this->recurseFiles("/", 0);
		$time = (hrtime(true)-$start)/1000000000;
		$tc = new ConvertTime(ConvertTime::SECONDS, ConvertTime::HMS);
		echo "Directories: ".$this->directories.PHP_EOL;
		echo "Files:       ".$this->files.PHP_EOL;
		echo "Directories:  ".number_format($this->directories).PHP_EOL;
		echo "Files:        ".number_format($this->files).PHP_EOL;
		echo "Processed:    ".number_format($this->processed).PHP_EOL;
		echo "Created:      ".number_format($this->created).PHP_EOL;
		echo "DB Size:      ".number_format(filesize(__DIR__."/catalog.sqlite")).PHP_EOL;
		echo "Add. DB Size: ".number_format(filesize(__DIR__."/catalog.sqlite")-$this->origSize).PHP_EOL;
		echo "Elapsed:      ".$tc->convert($time).PHP_EOL;
		echo "Versions:     ".number_format($this->pdo->result("select count(*) from d_version", array())).PHP_EOL;

	}
	
	function getEntryByPath($path): CatalogEntry {
		$exp = array_slice(explode("/", $path), 1);
		$entry = NULL;
		foreach($exp as $value) {
			if($entry==NULL) {
				$entry = CatalogEntry::fromName($this->pdo, $value, $entry);
			} else {
				$entry = CatalogEntry::fromName($this->pdo, $value, $entry);
			}
		}
	return $entry;
	}
	
	function runRestore() {
		/*
		 * $argv[2] is interpreted as an entry point from which to restore, ie
		 * if the user only wants to restore a part of the backup.
		 */
		if(isset($this->argv[2])) {
			$convert = new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE);
			$path = $convert->convert($this->argv[2]);
			$entry = $this->getEntryByPath($path);
			if($entry->getLatest()->type==self::TYPE_DIR) {
				$this->recurseCatalog($entry->id, 0, $path."/");
			} else {
				$this->restoreFile(dirname($path)."/", $entry);
			}
		return;
		}
		$this->recurseCatalog(NULL, 0, "/");
	}
}

$recurse = new Recurse($argv);
$recurse->run();