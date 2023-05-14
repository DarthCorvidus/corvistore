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
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_name = ? and dc_parent IS NULL ORDER BY dc_id, dvs_created DESC";
		} else {
			$param[] = $parent->id;
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_name = ? and dc_parent = ? ORDER BY dc_id, dvs_created DESC";
		}
		$stmt = $pdo->prepare($query);
		$stmt->execute($param);
		foreach($stmt as $key => $value) {
			if($key == 0) {
				$entry = new CatalogEntry($value);
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
		$this->createdAt = $array["dvs_created"];
		$this->type = $array["dvs_type"];
	}
}

class Recurse {
	private $exclude;
	private $pdo;
	const TYPE_MISS = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	private $argv;
	function __construct(array $argv) {
		$this->argv = $argv;
		$shared = new Shared();
		if(!file_exists(__DIR__."/catalog.sqlite")) {
			throw new Exception("Please create catalog.sqlite first, using default-catalog.sql");
		}
		$shared->useSQLite(__DIR__."/catalog.sqlite");
		$this->pdo = $shared->getEPDO();

		$this->exclude[] = "/bin";
		$this->exclude[] = "/boot";
		$this->exclude[] = "/dev";
		$this->exclude[] = "/home";
		$this->exclude[] = "/lib";
		$this->exclude[] = "/etc";
		$this->exclude[] = "/lib64";
		$this->exclude[] = "/media";
		$this->exclude[] = "/mnt";
		$this->exclude[] = "/opt";
		$this->exclude[] = "/proc";
		#$this->exclude[] = "/root";
		$this->exclude[] = "/srv";
		$this->exclude[] = "/run";
		$this->exclude[] = "/sbin";
		$this->exclude[] = "/storage";
		$this->exclude[] = "/sys";
		$this->exclude[] = "/usr";
		$this->exclude[] = "/virtual";
		$this->exclude[] = "/lost+found";
		#$this->exclude[] = "/tmp";
		#$this->exclude[] = "/var";
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
		$row = $this->pdo->row("select * from d_version where dc_id = ? order by dvs_created desc limit 1", $param);
		$size = filesize($path);
		$mtime = filemtime($path);
		if(empty($row) or $row["dvs_type"]!=self::TYPE_DIR) {
			echo "Creating version for directory ".$path.PHP_EOL;
			$create["dc_id"] = $id;
			#$create["dvs_size"] = $size;
			#$create["dvs_mtime"] = $mtime;
			$create["dvs_created"] = gmdate("Y-m-d H:i:s");
			$create["dvs_type"] = self::TYPE_DIR;
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
		$version["dvs_created"] = gmdate("Y-m-d H:i:s");
		$version["dvs_type"] = self::TYPE_FILE;
		$param[] = $version["dc_id"];
		$param[] = $version["dvs_size"];
		$param[] = $version["dvs_mtime"];
		$param[] = $version["dvs_type"];
		$row = $this->pdo->row("select * from d_version where dc_id = ? and dvs_size = ? and dvs_mtime = ? and dvs_type = ? order by dvs_created desc limit 1", $param);
		if(!empty($row)) {
			return;
		}
		echo "Creating version for file ".$path.PHP_EOL;
		$this->pdo->create("d_version", $version);
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
		$version["dvs_created"] = gmdate("Y-m-d H:i:s");
		$param[] = $version["dc_id"];
		$param[] = $version["dvs_size"];
		$param[] = $version["dvs_mtime"];
		$param[] = $version["dvs_type"];
		$row = $this->pdo->row("select * from d_version where dc_id = ? and dvs_size = ? and dvs_mtime = ? and dvs_type = ? order by dvs_created desc limit 1", $param);
		if(!empty($row)) {
			return;
		}
		echo "Flagging catalog entry ".$id." as deleted".PHP_EOL;
		$this->pdo->create("d_version", $version);
	}
	
	private function getFileId($path, $parentid = NULL)  {
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
			if(in_array($value, $this->exclude)) {
				continue;
			}
			$all[] = basename($value);
			if(is_dir($value)) {
				$directories[] = $value;
				continue;
			}
			if(is_file($value)) {
				$files[] = $value;
				continue;
			}
			
		}
		foreach($directories as $key => $value) {
			$id = $this->getFileId($value, $parentid);
			#echo str_repeat(" ", $depth)."+ [".$id."] ".basename($value).PHP_EOL;
			$this->recurseFiles($value, $depth+1, $id);
		}
		foreach($files as $key => $value) {
			$fileId = $this->getFileId($value, $parentid);
			#echo str_repeat(" ", $depth)." [".$fileId."] ".basename($value).PHP_EOL;
		}
		$stmt = $this->pdo->prepare("select dc_id, dc_name from d_catalog where dc_parent = ?");
		$stmt->execute(array($parentid));
		foreach($stmt as $key => $value) {
			if(!in_array($value["dc_name"], $all)) {
				$this->addDeleted($value["dc_id"]);
			}
		}
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
	
	function runBackup() {
		$this->recurseFiles("/", 0);
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