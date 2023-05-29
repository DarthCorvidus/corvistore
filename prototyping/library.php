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
	
	function store($fh) {
		$vh = fopen(__DIR__."/library/".$this->name, "a");
		$i = 0;
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
		}
		fclose($vh);
	}
	
	function update(EPDO $pdo) {
		$update["dvl_blocks_used"] = $this->blocksUsed;
		$pdo->update("d_volume", $update, array("dvl_id" => $this->id));
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
			$map["nvf_start"] = $free->blocksUsed;
			$free->store($fh);
			$free->update($this->pdo);
			$map["nvf_end"] = $free->blocksUsed;
			$map["dfl_id"] = $libfile->id;
			$this->pdo->create("n_volume2file", $map);
			$i++;
		}
		fclose($fh);
		#print_r($this->volumes);
	}
}

#$library = new Library(__DIR__."/library", 25);

if(!isset($argv[1])) {
	echo "Please supply mode, 'store' or 'restore'".PHP_EOL;
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