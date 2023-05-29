#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
class Volumes {
	private $volumes;
	private $names;
	function __construct() {
		$this->volumes = array();
		$this->names = array();
	}
	function addVolume(Volume $volume) {
		$this->volumes[] = $volume;
		$this->names[$volume->name] = $this->getCount()-1;
	}
	
	function getFree(): Volume {
		foreach($this->volumes as $key => $value) {
			if($value->used<$value->size) {
				return $value;
			}
		}
	}
	
	function getByName($name): Volume {
		return $this->volumes[$this->names[$name]];
	}
	
	function getCount(): int {
		return count($this->volumes);
	}
}

class LibFile {
	public $id;
	public $path;
	public $size;
	function __construct(array $array) {
		$this->id = $array["dfl_id"];
	}
	static function create(EPDO $pdo, string $path): LibFile {
		$row = $pdo->row("select * from d_file where dfl_path = ?", array($path));
		if(!empty($row)) {
			throw new Exception("File ".$path." already exists in library");
		}
		$create["dfl_path"] = $path;
		$create["dfl_size"] = filesize($path);
		$create["dfl_id"] = $pdo->create("d_file", $create);
	return new LibFile($create);
	}
}

class Volume {
	public $name;
	public $size;
	public $used;
	public $id;
	function __construct($array) {
		$this->id = $array["dvl_id"];
		$this->name = $array["dvl_name"];
		$this->size = $array["dvl_size"];
		$this->used = $array["dvl_used"];
	}
	
	function store($fh): int {
		$vh = fopen(__DIR__."/library02/".$this->name, "a");
		$i = 0;
		$written = 0;
		while(true) {
			#echo number_format($this->used)." ".number_format($this->size).PHP_EOL;
			if($this->used>=$this->size) {
				break;
			}
			if(!$read = fread($fh, 4096)) {
				break;
			}
			fwrite($vh, $read);
			$this->used += strlen($read);
			$written += strlen($read);
		}
		fclose($vh);
	return $written;
	}
	
	function read($write, $offset, $length) {
		$rh = fopen(__DIR__."/library02/".$this->name, "r");
		fseek($rh, $offset);
		#echo "Length: ".$length.PHP_EOL;
		while($length>=4096)  {
			$read = fread($rh, 4096);
			fwrite($write, $read);
			$length -= 4096;
			#echo $length.PHP_EOL;
		}
		if($length<4096 && $length!=0) {
			$read = fread($rh, $length);
			fwrite($write, $read);
		}
		fclose($rh);
	}
	
	function update(EPDO $pdo) {
		$update["dvl_used"] = $this->used;
		$pdo->update("d_volume", $update, array("dvl_id" => $this->id));
	}
}

class LibraryFiles implements TerminalTableModel {
	private $values = array();
	private $pdo;
	private $title;
	const NAME = 0;
	const SIZE = 1;
	const PARTS = 2;
	const MAX = 3;
	function __construct(EPDO $pdo) {
		$this->title[self::NAME] = "Name";
		$this->title[self::SIZE] = "Size";
		$this->title[self::PARTS] = "Parts";
		$this->pdo = $pdo;
	}
	public function getCell(int $col, int $row): string {
		return $this->values[$row][$col];
	}

	public function getColumns(): int {
		return self::MAX;
	}

	public function getRows(): int {
		return count($this->values);
	}

	public function getTitle(int $col): string {
		return $this->title[$col];
	}

	public function hasTitle(): bool {
		return true;
	}

	public function load() {
		$this->values = array();
		$stmt = $this->pdo->prepare("select dfl_path, dfl_size, count(dvl_id) as parts from d_file JOIN n_volume2file USING (dfl_id) JOIN d_volume USING (dvl_id) group by dfl_path, dfl_size");
		$stmt->execute(array());
		foreach($stmt as $value) {
			$entry = array_fill(0, self::MAX, "");
			$entry[self::NAME] = $value["dfl_path"];
			$entry[self::SIZE] = number_format($value["dfl_size"]);
			$entry[self::PARTS] = $value["parts"];
			$this->values[] = $entry;
		}
		
	}
}


class Library {
	private $blocksize = 4096;
	private $path;
	private $volsize = 512*1024*1024;
	private $volumes;
	private $pdo;
	private $blocksPerVol = 0;
	function __construct($path, int $volumes) {
		echo "Blocks per Volume: ".($this->volsize/$this->blocksize).PHP_EOL;
		$this->blocksPerVol = $this->volsize/$this->blocksize;
		$this->path = $path;
		$this->volumes = new Volumes();
		$shared = new Shared();
		if(!file_exists(__DIR__."/library02.sqlite")) {
			throw new Exception("Please create library.sqlite first, using library02.sql");
		}
		$shared->useSQLite(__DIR__."/library02.sqlite");
		$this->pdo = $shared->getEPDO();
		$stmt = $this->pdo->prepare("select * from d_volume");
		$stmt->execute(array());
		foreach($stmt as $key => $value) {
			$this->volumes->addVolume(new Volume($value));
		}
		if($this->volumes->getCount()==0) {
			$this->init();
		}
	}
	
	private function init() {
		for($i=0;$i<25;$i++) {
			$create["dvl_name"] = "A".sprintf("%04d", $i);
			$create["dvl_size"] = $this->volsize;
			$create["dvl_used"] = 0;
			$create["dvl_id"] = $this->pdo->create("d_volume", $create);
			$this->volumes->addVolume(new Volume($create));
			unset($create["dvl_id"]);
		}
	}
	
	function store(string $path) {
		$libfile = LibFile::create($this->pdo, $path);
		$fh = fopen($path, "r");
		
		$i = 1;
		while(!feof($fh)) {
			$free = $this->volumes->getFree();
			$map["nvf_part"] = $i;
			$map["nvf_offset"] = $free->used;
			$written = $free->store($fh);
			$free->update($this->pdo);
			$map["nvf_length"] = $written;
			$map["dfl_id"] = $libfile->id;
			$map["dvl_id"] = $free->id;
			$this->pdo->create("n_volume2file", $map);
			$i++;
		}
		fclose($fh);
		#print_r($this->volumes);
	}
	
	function restore(string $path) {
		$result = $this->pdo->result("select count(*) from d_file where dfl_path = ?", array($path));
		if($result==0) {
			throw new Exception("no such file.");
		}
		$stmt = $this->pdo->prepare("select * from d_file JOIN n_volume2file USING (dfl_id) JOIN d_volume USING (dvl_id) where dfl_path = ? order by nvf_part");
		$stmt->execute(array($path));
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		if(!file_exists(__DIR__."/restore")) {
			mkdir(__DIR__."/restore");
		}
		$output = fopen(__DIR__."/restore/".basename($path), "w");
		foreach($stmt as $value) {
			$volume = $this->volumes->getByName($value["dvl_name"]);
			$volume->read($output, $value["nvf_offset"], $value["nvf_length"]);
		}
		fclose($output);
		echo md5_file($path).PHP_EOL;
		echo md5_file(__DIR__."/restore/".basename($path)).PHP_EOL;
	}
	
	function listFiles() {
		$model = new LibraryFiles($this->pdo);
		$table = new TerminalTable($model);
		echo $table->printTable();
	}
}

#$library = new Library(__DIR__."/library", 25);

if(!isset($argv[1]) or !in_array($argv[1], array("store", "restore", "files"))) {
	echo "Please supply mode, one of: ". implode(", ", array("store", "restore", "files")).PHP_EOL;
	die();
}

if($argv[1]=="store" and !isset($argv[2])) {
	echo "Please supply file path.".PHP_EOL;
	die();
}
if($argv[1]=="store" and file_exists($argv[2]) and is_file($argv[2])) {
	$library = new Library(__DIR__."/library", 25);
	$library->store($argv[2]);
}

if($argv[1]=="files") {
	$library = new Library(__DIR__."/library", 25);
	$library->listFiles();
}

if($argv[1]=="restore" and !isset($argv[2])) {
	echo "Restore file is missing (original path).".PHP_EOL;
	die();
	#$library = new Library(__DIR__."/library", 25);
	#$library->store($argv[2]);
}

if($argv[1]=="restore" and isset($argv[2])) {
	$library = new Library(__DIR__."/library", 25);
	$library->restore($argv[2]);
}