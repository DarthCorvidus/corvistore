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
	static function fromName(EPDO $pdo, string $name): Storage {
		$row = $pdo->row("select * from d_storage where dst_name = ?", array($name));
		if($row==array()) {
			throw new Exception("Storage '".$name."' not available");
		}
		if($row["dst_type"] == "basic") {
			return new StorageBasic($pdo, $row["dst_name"], $row["dst_location"]);
		}
	}
	
	function save() {
		$insert["dst_name"] = $this->name;
		$insert["dst_location"] = $this->location;
		$insert["dst_type"] = $this->type;
		$this->pdo->create("d_storage", $insert);
	}

	static function define(EPDO $pdo, CommandParser $command) {
		if($command->getParam("type")=="basic") {
			$new = new StorageBasic($pdo, $command->getPositional(0), $command->getParam("location"));
			$new->save();
		}
	}
	
	function createStoragePool() {
		
	}
	#abstract function store();
	#abstract function retrieve();
}
