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
	private $id;
	private function __construct() {
	}
	
	static function define(EPDO $pdo, CommandParser $command) {
		$command->import(new CPModelPartition($pdo, CPModelPartition::MODE_DEFINE));
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
	
	static function update(EPDO $pdo, CommandParser $command) {
		$update = array();
		$command->import(new CPModelPartition($pdo, CPModelPartition::MODE_UPDATE));
		$partition = Partition::fromName($pdo, $command->getPositional(0));
		if($command->getParam("description")) {
			$update["dpt_description"] = $command->getParam("description");
		}
		if($command->getParam("copy")) {
			$copypart = Partition::fromName($pdo, $command->getParam("copy"));
			if($copypart->type != "copy") {
				throw new InvalidArgumentException("Partition ".$command->getParam("copy")." is no partition of type 'copy'");
			}
			$update["dpt_copy"] = $copypart->getId();
		}
		if(!empty($update)) {
			$pdo->update("d_partition", $update, array("dpt_id"=>$partition->getId()));
		}
	}
	
	static function fromArray(EPDO $pdo, array $array): Partition {
		$part = new Partition();
		$part->pdo = $pdo;
		$part->id = $array["dpt_id"];
		$part->name = $array["dpt_name"];
		$part->storage = Storage::fromId($pdo, $array["dst_id"]);
		$part->type = $array["dpt_type"];
	return $part;
	}
	
	static function fromName(EPDO $pdo, $name): Partition {
		$row = $pdo->row("select * from d_partition where dpt_name = ?", array($name));
		if(empty($row)) {
			throw new Exception("Partition '".$name."' does not exist.");
		}
	return self::fromArray($pdo, $row);
	}
	
	static function fromId(EPDO $pdo, $id): Partition {
		$row = $pdo->row("select * from d_partition where dpt_id = ?", array($id));
		if(empty($row)) {
			throw new Exception("Partition with id '".$id."' does not exist.");
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
	
	public function getId(): int {
		return $this->id;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function getStorageId(): int {
		return $this->storage->getId();
	}
	
	public function getType(): string {
		return $this->type;
	}
}
