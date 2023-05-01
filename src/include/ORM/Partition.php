<?php
/**
 * Partition
 * 
 * Partition within a storage
 *
 * @author Claus-Christoph Küthe
 */
class Partition {
	private $pdo;
	private $name;
	private $storage;
	private $type;
	private function __construct() {
	}
	
	static function define(EPDO $pdo, CommandParser $command) {
		$command->import(new CPModelPartition());
		$name = $command->getPositional(0);
		$storage = $command->getParam("storage");
		$type = $command->getParam("type");
		$part = new Partition();
		$part->pdo = $pdo;
		$part->name = $name;
		$part->storage = Storage::fromName($pdo, $storage);
		$part->type = $type;
		$part->create();
	}
	
	static function fromArray(EPDO $pdo, array $array): Partition {
		$part = new Partition();
		$part->pdo = $pdo;
		$part->name = $array["dpt_name"];
		$part->storage = Storage::fromId($pdo, $array["dst_id"]);
		$part->type = $array["dpt_type"];
	return $part;
	}
	
	static function fromName(EPDO $pdo, $name): Partition {
		$row = $pdo->row("select * from d_partition where dpt_name = ?", array($name));
		if(empty($row)) {
			throw new Exception("Partition '".$name.' does not exist.');
		}
	return self::fromArray($pdo, $row);
	}
	
	public function create() {
		$this->pdo->beginTransaction();
		$this->storage = Storage::fromName($this->pdo, $this->storage->getName());
		$new["dpt_name"] = $this->name;
		$new["dpt_type"] = $this->type;
		$new["dst_id"] = $this->storage->getId();
		$this->pdo->create("d_partition", $new);
		$this->pdo->commit();
	}
}