#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
class Volumes {
	private $volumes;
	function __construct() {
		$this->volumes = array();
	}
	function addVolume(Volume $volume) {
		$this->volumes[] = $volume;
	}
	
	function getFree(): Volume {
		foreach($this->volumes as $key => $value) {
			if($value->blocksUsed<$value->blocks) {
				return $value;
			}
		}
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
	public $blocks;
	public $blocksize;
	public $blocksUsed;
	public $id;
	function __construct($array) {
		$this->id = $array["dvl_id"];
		$this->name = $array["dvl_name"];
		$this->blocks = $array["dvl_blocks"];
		$this->blocksize = $array["dvl_blocksize"];
		$this->blocksUsed = $array["dvl_blocks_used"];
	}
	
	function store($fh): int {
		$vh = fopen(__DIR__."/library/".$this->name, "a");
		$i = 0;
		$written = 0;
		while(true) {
			if($this->blocksUsed==$this->blocks) {
				break;
			}
			if(!$read = fread($fh, $this->blocksize)) {
				break;
			}
			if(feof($fh)) {
				$read = str_pad($read, $this->blocksize, chr(0));
			}
			
			fwrite($vh, $read);
			$this->blocksUsed++;
			$written++;
		}
		fclose($vh);
	return $written;
	}
	
	function update(EPDO $pdo) {
		$update["dvl_blocks_used"] = $this->blocksUsed;
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
		if(!file_exists(__DIR__."/library.sqlite")) {
			throw new Exception("Please create library.sqlite first, using library.sql");
		}
		$shared->useSQLite(__DIR__."/library.sqlite");
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
			$create["dvl_blocks"] = $this->blocksPerVol;
			$create["dvl_blocks_used"] = 0;
			$create["dvl_blocksize"] = $this->blocksize;
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
			$map["nvf_offset"] = $free->blocksUsed;
			$free->store($fh);
			$free->update($this->pdo);
			$map["nvf_length"] = $free->blocksUsed;
			$map["dfl_id"] = $libfile->id;
			$map["dvl_id"] = $free->id;
			$this->pdo->create("n_volume2file", $map);
			$i++;
		}
		fclose($fh);
		#print_r($this->volumes);
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

/*
if($argv[1]=="restore" and !isset($argv[2])) {
	echo "Restore file is missing (original path).".PHP_EOL;
	die();
	#$library = new Library(__DIR__."/library", 25);
	#$library->store($argv[2]);
}
*/