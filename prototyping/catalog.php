#!/usr/bin/php
<?php
require_once __DIR__."/../vendor/autoload.php";
#if(!file_exists($argv[1])) {
#	echo "No valid path.".PHP_EOL;
#}

class Recurse {
	private $exclude;
	private $pdo;
	const TYPE_MISS = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	function __construct() {
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
	
	private function recurse($path, $depth, $parentid = NULL) {
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
			$this->recurse($value, $depth+1, $id);
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
	
	function run() {
		$this->recurse("/", 0);
	}
}

$recurse = new Recurse();
$recurse->run();
