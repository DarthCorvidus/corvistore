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

class FlatEntry {
	public $id;
	public $path;
	public $parent;
	public $size;
	public $mtime;
	public $createdAt;
	public $type;
	function __construct(array $array) {
		$this->id = $array["dfl_id"];
		$this->path = $array["dfl_path"];
		$this->size = $array["dfl_size"];
		$this->mtime = $array["dfl_mtime"];
		$this->createdAt = $array["dfl_created_epoch"];
		$this->type = $array["dfl_type"];
		$this->owner = $array["dfl_owner"];
		$this->group = $array["dfl_group"];
		$this->permissions = $array["dfl_permissions"];
	}
}

class VersionEntry {
	public $catalogId;
	function __construct(array $array) {
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
	function __construct(array $argv) {
		$this->argv = $argv;
		$shared = new Shared();
		if(!file_exists(__DIR__."/flat.sqlite")) {
			throw new Exception("Please create catalog.sqlite first, using default-catalog.sql");
		}
		$shared->useSQLite(__DIR__."/flat.sqlite");
		$this->origSize = filesize(__DIR__."/flat.sqlite");
		$this->pdo = $shared->getEPDO();
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
	private function addVersionDir($path) {
		$param[] = $path;
		/*
		 * Gets the first result - as we want to get the latest, we have to use
		 * descending order here.
		 */
		$row = $this->pdo->row("select * from d_flat where dfl_path = ? order by dfl_created_epoch desc limit 1", $param);
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
			$create["dfl_path"] = $path;
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
	
	private function addVersionFile($path) {
		$version["dfl_size"] = filesize($path);
		$version["dfl_mtime"] = filemtime($path);
		$version["dfl_created_local"] = date("Y-m-d H:i:sP");
		$version["dfl_created_epoch"] = mktime();
		$version["dfl_type"] = self::TYPE_FILE;
		$version["dfl_permissions"] = fileperms($path);
		$version["dfl_owner"] = $this->fileowner($path);
		$version["dfl_group"] = $this->filegroup($path);
		$version["dfl_path"] = $path;
		
		$param[] = $version["dfl_path"];
		$param[] = $version["dfl_size"];
		$param[] = $version["dfl_mtime"];
		$param[] = $version["dfl_type"];
		$row = $this->pdo->row("select * from d_flat where dfl_path = ? and dfl_size = ? and dfl_mtime = ? and dfl_type = ? order by dfl_created_epoch desc limit 1", $param);
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
	
	private function getFileId($path, $parentid = NULL)  {
		if(is_dir($path)) {
			$this->addVersionDir($path);
		}
		if(is_file($path)) {
			$this->addVersionFile($path);
		}

	}
	
	private function process(string $path) {
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
		foreach($process as $key => $value) {
			$this->getFileId($value);
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
	
	private function restoreFile(FlatEntry $entry) {
		if(!file_exists($entry->path)) {
			$this->resRestored++;
			echo $entry->path." missing, would be restored".PHP_EOL;
			return;
		}
		$mtime = filemtime($entry->path);
		if($mtime==$entry->mtime) {
			$this->resEqual++;
			#echo $entry->path." is equal, would ignore.".PHP_EOL;
		}

		if($mtime>$entry->mtime) {
			$this->resNewer++;
			#echo $entry->path." is newer, would ignore.".PHP_EOL;
		}

		if($mtime<$entry->mtime) {
			$this->resOlder++;
			#echo $entry->path." is older, would prompt.".PHP_EOL;
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
		$stmt = $this->pdo->prepare("select dfl_path, dfl_type from d_flat");
		$stmt->execute(array());
		foreach($stmt as $key => $value) {
			if(!file_exists($value["dfl_path"])) {
				$todelete[$value["dfl_path"]] = $value["dfl_type"];
			}
		}
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
		echo "Directories:  ".number_format($this->directories).PHP_EOL;
		echo "Files:        ".number_format($this->files).PHP_EOL;
		echo "Processed:    ".number_format($this->processed).PHP_EOL;
		echo "Created:      ".number_format($this->created).PHP_EOL;
		echo "DB Size:      ".number_format(filesize(__DIR__."/flat.sqlite")).PHP_EOL;
		echo "Add. DB Size: ".number_format(filesize(__DIR__."/flat.sqlite")-$this->origSize).PHP_EOL;
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
		#$sql[] = "select t.dfl_path, t.dfl_created_local, t.dfl_type from d_flat t";
		$param = array();
		$sql[] = "select t.* from d_flat t";
		if(isset($this->argv[2])) {
			/*
			 * $argv[2] is interpreted as an entry point from which to restore, ie
			 * if the user only wants to restore a part of the backup.
			 * 
			 * The current implementation is, however, too primitive and flawed.
			 * If I'd do 'flat.php restore /home/alex', it would restore
			 * /home/alexander and /home/alexandra too
			 */
			$sql[] = "INNER JOIN (select dfl_path, max(dfl_created_epoch) as maxdate from d_flat where dfl_path LIKE ? group by dfl_path) tm ";
			$param[] = $this->argv[2]."%";
		} else {
			$sql[] = "INNER JOIN (select dfl_path, max(dfl_created_epoch) as maxdate from d_flat group by dfl_path) tm ";
		}
		
		$sql[] = "on t.dfl_path = tm.dfl_path and t.dfl_created_epoch = tm.maxdate ";
		$sql[] = "order by t.dfl_path";
		$stmt = $this->pdo->prepare(implode(" ", $sql));
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute($param);
		$count = 0;
		foreach($stmt as $key => $value) {
			$this->resExamined++;
			$entry = new FlatEntry($value);
			$this->restoreFile($entry);
		}
		echo "Examined: ".number_format($this->resExamined).PHP_EOL;
		echo "Older:    ".number_format($this->resOlder).PHP_EOL;
		echo "Equal:    ".number_format($this->resEqual).PHP_EOL;
		echo "Newer:    ".number_format($this->resNewer).PHP_EOL;
		echo "Missing:  ".number_format($this->resRestored).PHP_EOL;
	}
}

$recurse = new Recurse($argv);
$recurse->run();