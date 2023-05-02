<?php
/**
 * Partition
 * 
 * Nodes are backup targets for different backup sources, ie say servers.
 *
 * @author Claus-Christoph Küthe
 */
class Node {
	private $pdo;
	private $id;
	private $name;
	private $policy;
	private function __construct() {
		;
	}
	static function define(EPDO $pdo, CommandParser $commandParser) {
		$commandParser->import(new CPModelNode());
		$node = new Node();
		$node->pdo = $pdo;
		$node->name = $commandParser->getPositional(0);
		$node->policy = Policy::fromName($pdo, $commandParser->getParam("policy"));
		$node->create();
	}
	
	private function create() {
		$new = array();
		$new["dnd_name"] = $this->name;
		$new["dpo_id"] = Policy::fromId($this->pdo, $this->policy->getId())->getId();
		$this->pdo->create("d_node", $new);
	}
	
	static function fromArray(EPDO $pdo, array $array): Node {
		$node = new Node();
		$node->pdo = $pdo;
		$node->id = (int)$array["dnd_id"];
		$node->name = $array["dnd_name"];
		$node->policy = Policy::fromId($pdo, $array["dpo_id"]);
	return $node;
	}

	static function fromName(EPDO $pdo, string $name): Node {
		$row = $pdo->row("select * from d_node where dnd_name = ?", array($name));
		if(empty($row)) {
			throw new InvalidArgumentException("Node '".$name."' does not exist.");
		}
	return self::fromArray($pdo, $row);
	}
	
	static function fromId(EPDO $pdo, int $id): Node {
		$row = $pdo->row("select * from d_node where dnd_id = ?", array($id));
		if(empty($row)) {
			throw new RuntimeException("Node with id '".$id."' does not exist.");
		}
	return self::fromArray($pdo, $row);
	}
	
	function getId(): int {
		return $this->id;
	}
	
	function getName(): string {
		return $this->name;
	}
	
	function getPolicy(): Policy {
		return $this->policy;
	}
}
