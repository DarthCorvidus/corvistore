<?php
class ModeNode implements Mode{
	private $pdo;
	private $catalog;
	private $quit = FALSE;
	private $conn;
	function __construct(EPDO $pdo, string $node, $conn) {
		$this->pdo = $pdo;
		$this->node = Node::fromName($this->pdo, $node);
		$this->catalog = new Catalog($this->pdo, $this->node);
		$this->conn = $conn;
	}
	public function onServerMessage(string $message) {
		if(strtoupper($message)=="QUIT") {
			$this->quit = TRUE;
			return "End connection.";
		}
		$exp = explode(" ", $message);
		$exp[0] = strtoupper($exp[0]);
		if(count($exp)==1) {
			#return $this->handleOne($exp);
		}

		if(count($exp)==2) {
			$this->handleTwo($exp);
			return;
		}

		if(count($exp)==3) {
			$this->handleThree($exp);
			return;
		}
	return "Invalid command ".$message;
	}
	
	public function handleTwo(array $command): string {
		if($command[0]=="GET" and strtoupper($command[1])=="CATALOG") {
			$entries = $this->catalog->getEntries();
			$serialized = serialize($entries);
			socket_write($this->conn, \IntVal::uint32LE()->putValue(strlen($serialized)));
			socket_write($this->conn, $serialized);
			#return serialize($entries);
		}
	return "Invalid command.";
	}
	
	public function handleThree(array $command) {
		if($command[0]=="GET" and strtoupper($command[1])=="CATALOG") {
			$entries = $this->catalog->getEntries($command[2]);
			$serialized = serialize($entries);
			socket_write($this->conn, \IntVal::uint32LE()->putValue(strlen($serialized)));
			socket_write($this->conn, $serialized);
		}
	}
	
	public function isQuit(): bool {
		return $this->quit;
	}
}
