<?php
/**
 * Partition
 * 
 * Nodes are backup targets for different backup sources, ie say servers.
 *
 * @author Claus-Christoph Küthe
 */
class User {
	private $pdo;
	private $id;
	private $name;
	private $salt;
	private $password;
	private function __construct() {
		;
	}
	static function define(EPDO $pdo, CommandParser $commandParser) {
		$commandParser->import(new CPModelUser($pdo, CPModelUser::MODE_DEFINE));
		$user = new User();
		$user->pdo = $pdo;
		$user->name = $commandParser->getPositional(0);
		$user->salt = sha1(random_bytes(256));
		$user->password = sha1($commandParser->getParam("password").$user->salt);
		$user->create();
	return "User ".$user->name." created.";
	}
	
	static function update(EPDO $pdo, CommandParser $command): string {
		$update = array();
		$command->import(new CPModelNode($pdo, CPModelNode::MODE_UPDATE));
		if($command->getParam("password")) {
			$salt = sha1(random_bytes(256));
			$update["du_salt"] = $salt;
			$update["du_password"] = sha1($command->getParam("password").$salt);
		}

		$user = User::fromName($pdo, $command->getPositional(0));
		if(!empty($update)) {
			$pdo->update("d_user", $update, array("du_id"=>$user->getId()));
			return "User ".$command->getPositional(0)." updated";
		}
	return "No changes.";
	}

	static function authenticate(EPDO $pdo, string $conjoined) {
		$exp = explode(":", $conjoined, 2);
		if(count($exp)==1) {
			throw new Exception("Unable to read password for node ".$exp[0]);
		}
		$node = User::fromName($pdo, $exp[0]);
		if(sha1($exp[1].$node->getSalt())!=$node->getPassword()) {
			throw new Exception("Unable to authenticate");
		}
	return $node;
	}

	
	private function create() {
		$new = array();
		$new["du_name"] = $this->name;
		$new["du_password"] = $this->password;
		$new["du_salt"] = $this->salt;
		$this->pdo->create("d_user", $new);
	}
	
	static function fromArray(EPDO $pdo, array $array): User {
		$node = new User();
		$node->pdo = $pdo;
		$node->id = (int)$array["du_id"];
		$node->name = $array["du_name"];
		$node->salt = $array["du_salt"];
		$node->password = $array["du_password"];
	return $node;
	}

	static function fromName(EPDO $pdo, string $name): User {
		$row = $pdo->row("select * from d_user where du_name = ?", array($name));
		if(empty($row)) {
			throw new InvalidArgumentException("User '".$name."' does not exist.");
		}
	return self::fromArray($pdo, $row);
	}
	
	static function fromId(EPDO $pdo, int $id): User {
		$row = $pdo->row("select * from d_user where du_id = ?", array($id));
		if(empty($row)) {
			throw new RuntimeException("User with id '".$id."' does not exist.");
		}
	return self::fromArray($pdo, $row);
	}
	
	function getId(): int {
		return $this->id;
	}
	
	function getName(): string {
		return $this->name;
	}
	
	function getSalt(): string {
		return $this->salt;
	}
	
	function getPassword(): string {
		return $this->password;
	}
}
