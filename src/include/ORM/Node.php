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
	private $salt;
	private $password;
	private function __construct() {
		;
	}
	static function define(EPDO $pdo, CommandParser $commandParser) {
		$commandParser->import(new CPModelNode($pdo, CPModelNode::MODE_DEFINE));
		$node = new Node();
		$node->pdo = $pdo;
		$node->name = $commandParser->getPositional(0);
		$node->policy = Policy::fromName($pdo, $commandParser->getParam("policy"));
		$node->salt = sha1(random_bytes(256));
		$node->password = sha1($commandParser->getParam("password").$node->salt);
		$node->create();
	}
	
	static function update(EPDO $pdo, CommandParser $command): string {
		$update = array();
		$command->import(new CPModelNode($pdo, CPModelNode::MODE_UPDATE));
		if($command->getParam("password")) {
			$salt = sha1(random_bytes(256));
			$update["dnd_salt"] = $salt;
			$update["dnd_password"] = sha1($command->getParam("password").$salt);
		}

		$node = Node::fromName($pdo, $command->getPositional(0));
		if(!empty($update)) {
			$pdo->update("d_node", $update, array("dnd_id"=>$node->getId()));
			return "Node ".$command->getPositional(0)." updated";
		}
	return "No changes.";
	}
	
	static function authenticate(EPDO $pdo, string $conjoined) {
		$exp = explode(":", $conjoined, 2);
		if(count($exp)==1) {
			throw new Exception("Unable to read password for node ".$exp[0]);
		}
		$node = Node::fromName($pdo, $exp[0]);
		if(sha1($exp[1].$node->getSalt())!=$node->getPassword()) {
			throw new Exception("Unable to authenticate");
		}
	return $node;
	}

	
	private function create() {
		$new = array();
		$new["dnd_name"] = $this->name;
		$new["dpo_id"] = Policy::fromId($this->pdo, $this->policy->getId())->getId();
		$new["dnd_salt"] = $this->salt;
		$new["dnd_password"] = $this->password;
		$this->pdo->create("d_node", $new);
	}
	
	static function fromArray(EPDO $pdo, array $array): Node {
		$node = new Node();
		$node->pdo = $pdo;
		$node->id = (int)$array["dnd_id"];
		$node->name = $array["dnd_name"];
		$node->policy = Policy::fromId($pdo, $array["dpo_id"]);
		$node->salt = $array["dnd_salt"];
		$node->password = $array["dnd_password"];
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
	
	function getSalt(): string {
		return $this->salt;
	}
	
	function getPassword(): string {
		return $this->password;
	}
}
