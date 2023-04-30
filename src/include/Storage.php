<?php
/**
 * Storage
 * 
 * Abstract class for Storage. Storage describes how data will be stored.
 *
 * @author Claus-Christoph Küthe
 */
abstract class Storage {
	protected $pdo;
	protected $name;
	protected $type;
	protected $location;
	protected $id;
	protected function __construct() {
		;
	}
	static function fromArray(EPDO $pdo, array $array): Storage {
		if($array["dst_type"] == "basic") {
			$storage = new StorageBasic();
			$storage->pdo = $pdo;
			$storage->name = $array["dst_name"];
			$storage->location = $array["dst_location"];
			$storage->type = $array["dst_type"];
			$storage->id = $array["dst_id"];
			return $storage;
		}
	}
	
	static function fromName(EPDO $pdo, string $name): Storage {
		$row = $pdo->row("select * from d_storage where dst_name = ?", array($name));
		if($row==array()) {
			throw new Exception("Storage '".$name."' not available");
		}
		return self::fromArray($pdo, $row);
	}
	
	static function fromId(EPDO $pdo, int $id): Storage {
		$row = $pdo->row("select * from d_storage where dst_id = ?", array($id));
		if($row==array()) {
			throw new Exception("Storage with id '".$id."' not available");
		}
		return self::fromArray($pdo, $row);
	}
	
	function create() {
		$insert["dst_name"] = $this->name;
		$insert["dst_location"] = $this->location;
		$insert["dst_type"] = $this->type;
		$this->id = $this->pdo->create("d_storage", $insert);
	}

	static function define(EPDO $pdo, CommandParser $command) {
		$command->import(new CPModelStorage);
		if($command->getParam("type")=="basic") {
			$new = new StorageBasic();
			$new->pdo = $pdo;
			$new->type = "basic";
			$new->name = $command->getPositional(0);
			$location = $command->getParam("location");
			if(!file_exists($location)) {
				throw new InvalidArgumentException("Location '".$location."' does not exist.");
			}
			if(!is_dir($location)) {
				throw new InvalidArgumentException("Location '".$location."' is not a directory.");
			}
			$new->location = $location;
			$new->create();
		return;
		}
		throw new Exception("Invalid Storage type '".$command->getParam("type")."'");
	}
	
	function getName(): string {
		return $this->name;
	}
	
	function getId(): string {
		return $this->id;
	}
	
	function createStoragePool() {
		
	}
	#abstract function store();
	#abstract function retrieve();
}
