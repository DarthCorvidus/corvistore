<?php
/**
 * Represents a policy object.
 *
 * @author Claus-Christoph Küthe
 */
class Policy {
	private EPDO $pdo;
	private string $name;
	private int $id;
	private int $retentionExists = 0;
	private int $retentionDeleted = 0;
	private int $versionExists = 0;
	private int $versionDeleted = 0;
	private Partition $partition;
	private function __construct() {
	}
	static function define(EPDO $pdo, CommandParser $parser): void {
		$parser->import(new CPModelPolicy());
		$policy = new Policy();
		$policy->pdo = $pdo;
		$policy->name = $parser->getPositional(0);
		$policy->partition = Partition::fromName($pdo, $parser->getParam("partition"));
		$policy->versionExists = (int)$parser->getParam("verexists");
		$policy->versionDeleted = (int)$parser->getParam("verdeleted");
		$policy->retentionExists =  (int)$parser->getParam("retexists");
		$policy->retentionDeleted =  (int)$parser->getParam("retdeleted");
		$policy->create();
	}
	
	static function fromArray(EPDO $pdo, array $array): Policy {
		$policy = new Policy();
		$policy->pdo = $pdo;
		$policy->id = $array["dpo_id"];
		$policy->name = $array["dpo_name"];
		$policy->partition = Partition::fromId($pdo, $array["dpt_id"]);
		$policy->versionExists = $array["dpo_version_exists"];
		$policy->versionDeleted = $array["dpo_version_deleted"];
		$policy->retentionExists = $array["dpo_retention_exists"];
		$policy->retentionDeleted = $array["dpo_retention_deleted"];
	return $policy;
	}
	
	static function fromName(EPDO $pdo, string $name): Policy {
		$array = $pdo->row("select * from d_policy where dpo_name = ?", array($name));
		if(empty($array)) {
			throw new InvalidArgumentException(sprintf("Policy with name '%s' does not exist.", $name));
		}
	return Policy::fromArray($pdo, $array);
	}
	
	static function fromId(EPDO $pdo, int $id): Policy {
		$array = $pdo->row("select * from d_policy where dpo_id = ?", array($id));
		if(empty($array)) {
			throw new RuntimeException(sprintf("Policy with id '%d' does not exist.", $id));
		}
	return Policy::fromArray($pdo, $array);
	}
	
	function create(): void {
		$new = [];
		$new["dpo_name"] = $this->name;
		$new["dpt_id"] = Partition::fromId($this->pdo, $this->partition->getId())->getId();
		$new["dpo_version_exists"] = $this->versionExists;
		$new["dpo_version_deleted"] = $this->versionDeleted;
		$new["dpo_retention_exists"] = $this->retentionExists;
		$new["dpo_retention_deleted"] = $this->retentionDeleted;
		$this->pdo->create("d_policy", $new);
	}
	
	function getId():int {
		return $this->id;
	}
	
	function getName(): string {
		return $this->name;
	}
	
	function getVersionExists(): int {
		return $this->versionExists;
	}
	
	function getVersionDeleted(): int {
		return $this->versionDeleted;
	}
	
	function getRetentionExists(): int {
		return $this->retentionExists;
	}
	
	function getRetentionDeleted(): int {
		return $this->retentionDeleted;
	}
	
	function getPartition(): Partition {
		return $this->partition;
	}
	
	
	
}
