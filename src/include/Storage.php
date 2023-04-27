<?php
/**
 * Storage
 * 
 * Abstract class for Storage. Storage describes how data will be stored.
 *
 * @author Claus-Christoph Küthe
 */
abstract class Storage {
	protected $name;
	protected $type;
	protected $location;
	protected $id;
	static function fromArray(EPDO $pdo, array $array): Storage {
		if($array["dst_type"] == "basic") {
			$storage = new StorageBasic($pdo, $array["dst_name"], $array["dst_location"]);
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
		if($command->getParam("type")=="basic") {
			$new = new StorageBasic($pdo, $command->getPositional(0), $command->getParam("location"));
			$new->create();
		}
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
