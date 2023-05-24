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
		print_r($array);
		$this->versions[] = VersionEntry::fromArray($array);
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
		$this->createdAt = $array["dvs_created"];
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
	function __construct(array $argv) {
		$this->argv = $argv;
		$shared = new Shared();
		if(!file_exists(__DIR__."/flat-normalized.sqlite")) {
			throw new Exception("Please create catalog.sqlite first, using default-catalog.sql");
		}
		$shared->useSQLite(__DIR__."/flat-normalized.sqlite");
		$this->origSize = filesize(__DIR__."/flat-normalized.sqlite");
		$this->pdo = $shared->getEPDO();
		$this->inex = new InEx();
		$this->inex = new InEx();
		#$this->inex->addExclude("/home/");
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
	private function addVersionDir($path, $pathId) {
		$param[] = $pathId;
		/*
		 * Gets the first result - as we want to get the latest, we have to use
		 * descending order here.
		 */
		$row = $this->pdo->row("select * from d_flat where lp_id = ? order by dfl_created_epoch desc limit 1", $param);
		$size = filesize($path);
		$mtime = filemtime($path);
		if(empty($row) or $row["dfl_type"]!=self::TYPE_DIR) {
			#echo "Creating version for directory ".$path.PHP_EOL;
			#$create["dvs_size"] = $size;
			#$create["dvs_mtime"] = $mtime;
			/*
			 * The race condition here - mktime could be 1 s ahead of date - is
			 * negligible ;-).
			 */
			$create["lp_id"] = $pathId;
			$create["dfl_created_local"] = date("Y-m-d H:i:sP");
			$create["dfl_created_epoch"] = mktime();
			$create["dfl_type"] = self::TYPE_DIR;
			$create["dfl_permissions"] = fileperms($path);
			$create["dfl_owner"] = $this->fileowner($path);
			$create["dfl_group"] = $this->filegroup($path);
			$this->pdo->create("d_flat", $create);
			$this->created++;
			return;
		}
		#if($row["dvs_size"]!=$size or $row["dvs_mtime"]!=$mtime) {
		#	echo "Updating version of directory ".$path.PHP_EOL;
		#	$update["dvs_size"] = $size;
		#	$update["dvs_mtime"] = $mtime;
		#	$this->pdo->update("d_version", $update, array("dvs_id"=>$row["dvs_id"]));
		#}
	}
	
	private function addVersionFile($path, $pathId) {
		$version["dfl_size"] = filesize($path);
		$version["dfl_mtime"] = filemtime($path);
		$version["dfl_created_local"] = date("Y-m-d H:i:sP");
		$version["dfl_created_epoch"] = mktime();
		$version["dfl_type"] = self::TYPE_FILE;
		$version["dfl_permissions"] = fileperms($path);
		$version["dfl_owner"] = $this->fileowner($path);
		$version["dfl_group"] = $this->filegroup($path);
		$version["lp_id"] = $pathId;
		
		$param[] = $version["lp_id"];
		$param[] = $version["dfl_size"];
		$param[] = $version["dfl_mtime"];
		$param[] = $version["dfl_type"];
		$row = $this->pdo->row("select * from d_flat where lp_id = ? and dfl_size = ? and dfl_mtime = ? and dfl_type = ? order by dfl_created_epoch desc limit 1", $param);
		if(empty($row)) {
			#echo "Creating version for file ".$path.PHP_EOL;
			$this->pdo->create("d_flat", $version);
			$this->created++;
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
	
	private function getFileId($path, $pathId)  {
		if(is_dir($path)) {
			$this->addVersionDir($path, $pathId);
		}
		if(is_file($path)) {
			$this->addVersionFile($path, $pathId);
		}

	}
	
	private function createPath(string $path) {
		$result = $this->pdo->result("select lp_id from l_path where lp_path = ?", array($path));
		if($result===NULL or $result==="" or $result===FALSE) {
			$create["lp_path"] = $path;
			return $this->pdo->create("l_path", $create);
		}
	return $result;
	}
	
	private function process(string $path) {
		#if($this->total%5000==0) {
		#	echo $this->total." processed".PHP_EOL;
		#}
		if(count($this->process)<100) {
			$this->process[] = $path;
		return;
		}
		
		$this->processArray($this->process);
		$this->process = array();
	}
	
	private function processArray(array $process) {
		//echo "Processing ".count($process)." entries...".PHP_EOL;
		$this->pdo->beginTransaction();
		$pathId = array();
		foreach($process as $key => $value) {
			$pathId[$key] = $this->createPath($value);
			#$this->getFileId($value);
		}

		foreach($process as $key => $value) {
			$this->getFileId($value, $pathId[$key]);
		}
		
		
		$this->pdo->commit();
	}
	
	private function recurseFiles($path) {
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
				$this->directories++;
				$this->processed++;
				$directories[] = $value;
				$this->process($value);
				$this->recurseFiles($value);
				continue;
			}
			if(is_file($value) and $this->inex->isValid($path)) {
				$this->files++;
				$this->processed++;
				
				$this->process($value);
				
				$files[] = $value;
				continue;
			}

		}
		#$directoriesCreated = array();
		#$this->pdo->beginTransaction();
		#foreach($directories as $key => $value) {
		#	$this->directories++;
		#	//$id = $this->getFileId($value, $parentid);
		#	$directoriesCreated[] = $value;
		#	#echo str_repeat(" ", $depth)."+ [".$id."] ".basename($value).PHP_EOL;
		#	
		#}
		#$this->pdo->commit();
		
		#foreach($directoriesCreated as $key => $value) {
		#	$this->recurseFiles($value, $depth+1, $key);
		#}
		

		#$this->pdo->beginTransaction();
		#foreach($files as $key => $value) {
		#	$this->files++;
		#	$fileId = $this->getFileId($value, $parentid);
		#	#echo str_repeat(" ", $depth)." [".$fileId."] ".basename($value).PHP_EOL;
		#}
		#$this->pdo->commit();

		#$this->pdo->beginTransaction();
		#$stmt = $this->pdo->prepare("select dc_id, dc_name from d_catalog where dc_parent = ?");
		#$stmt->execute(array($parentid));
		#foreach($stmt as $key => $value) {
		#	if(!in_array($value["dc_name"], $all)) {
		#		$this->addDeleted($value["dc_id"]);
		#	}
		#}
		#$this->pdo->commit();
		
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
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_parent IS NULL ORDER BY dc_id, dvs_created ASC";
		} else {
			$param[] = $parent;
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_parent = ? ORDER BY dc_id, dvs_created ASC";
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
				#echo $path.$value->name."/".PHP_EOL;
				#$this->recurseCatalog($value->id, $depth+1, $path.$value->name."/");
			}
			if($version->type==self::TYPE_FILE) {
				$this->restoreFile($path, $value);
			}
		}
	}
	
	private function restoreFile($path, CatalogEntry $entry) {
		$version = $entry->getLatest();
		$filepath = $path.$entry->name;
		if(!file_exists($path.$entry->name)) {
			echo $filepath." missing, would be restored".PHP_EOL;
			return;
		}
		$mtime = filemtime($filepath);
		if($mtime>$version->mtime) {
			echo $filepath." is newer, would ignore.".PHP_EOL;
		}

		if(filemtime($filepath)<$version->mtime) {
			echo $filepath." is older, would prompt.".PHP_EOL;
		}

	}
	
	function run() {
		if($this->argv[1]=="restore") {
			$this->runRestore();
		}

		if($this->argv[1]=="backup") {
			$this->runBackup();
		}
	}
	
	function checkDeleted() {
		$todelete = array();
		$stmt = $this->pdo->prepare("select lp_path, dfl_type from d_flat JOIN l_path USING (lp_id) order by dfl_created_epoch");
		$stmt->execute(array());
		foreach($stmt as $key => $value) {
			if(!file_exists($value["lp_path"])) {
				$todelete[$value["lp_path"]] = $value["dfl_type"];
			}
		}
	return;
		foreach($todelete as $key => $value) {
			if($value==0) {
				continue;
			}
			
			echo "Flag ".$key." as deleted.".PHP_EOL;
			$new["dfl_path"] = $key;
			$new["dfl_type"] = self::TYPE_MISS;
			$new["dfl_created_local"] = date("Y-m-d H:i:sP");
			$new["dfl_created_epoch"] = mktime();
			$this->pdo->create("d_flat", $new);
		}
	}
	
	function runBackup() {
		$start = hrtime(true);
		$this->recurseFiles("/", 0);
		$this->processArray($this->process);
		#echo "Checking for deleted files".PHP_EOL;
		#$this->checkDeleted();
		$time = (hrtime(true)-$start)/1000000000;
		$tc = new ConvertTime(ConvertTime::SECONDS, ConvertTime::HMS);
		$tc = new ConvertTime(ConvertTime::SECONDS, ConvertTime::HMS);
		echo "Directories:  ".number_format($this->directories).PHP_EOL;
		echo "Files:        ".number_format($this->files).PHP_EOL;
		echo "Processed:    ".number_format($this->processed).PHP_EOL;
		echo "Created:      ".number_format($this->created).PHP_EOL;
		echo "DB Size:      ".number_format(filesize(__DIR__."/flat-normalized.sqlite")).PHP_EOL;
		echo "Add. DB Size: ".number_format(filesize(__DIR__."/flat-normalized.sqlite")-$this->origSize).PHP_EOL;
		echo "Elapsed:      ".$tc->convert($time).PHP_EOL;
		echo "Versions:     ".number_format($this->pdo->result("select count(*) from d_flat", array())).PHP_EOL;
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