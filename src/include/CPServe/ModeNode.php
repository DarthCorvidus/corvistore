<?php
class ModeNode implements \Net\ProtocolListener {
	private $pdo;
	private $catalog;
	private $quit = FALSE;
	private $conn;
	private $partition;
	function __construct(EPDO $pdo, string $node, $conn) {
		$this->pdo = $pdo;
		$this->node = Node::fromName($this->pdo, $node);
		$this->catalog = new Catalog($this->pdo, $this->node);
		$this->partition = $this->node->getPolicy()->getPartition();
		#$this->conn = $conn;
	}
	
	public function onCommand(string $command, \Net\Protocol $protocol) {
		$exp = explode(" ", $command);
		if(count($exp)==1) {
			$this->handleOne($exp, $protocol);
		return;
		}

		if(count($exp)==2) {
			$this->handleTwo($exp, $protocol);
			return;
		}

		if(count($exp)==3) {
			$this->handleThree($exp, $protocol);
			return;
		}
	return "Invalid command ".$message;
	}
	
	public function handleTwo(array $command, \Net\Protocol $protocol) {
		/*
		if($command[0]=="GET" and strtoupper($command[1])=="CATALOG") {
			$entries = $this->catalog->getEntries();
			$protocol->sendSerializePHP($entries);
			#$entries = $this->catalog->getEntries();
			#$serialized = serialize($entries);
			#socket_write($this->conn, \IntVal::uint32LE()->putValue(strlen($serialized)));
			#socket_write($this->conn, $serialized);
			#return serialize($entries);
		return;
		}
		 * 
		 */
		#if($command[0]=="CREATE" and $command[1]=="FILE") {
		#	$file = $protocol->getUnserializePHP();
		#	$new = $this->catalog->newEntry($file);
		#	$protocol->sendSerializePHP($new);
		#return;
		#}

		if($command[0]=="DELETE" and $command[1]=="ENTRY") {
			$entry = $protocol->getUnserializePHP();
			$this->catalog->deleteEntry($entry);
		}

	return "Invalid command.";
	}
	
	public function handleThree(array $command, \Net\Protocol $protocol) {
		if($command[0]=="GET" and strtoupper($command[1])=="CATALOG") {
			$entries = $this->catalog->getEntries($command[2]);
			$protocol->sendSerializePHP($entries);
		return;
		}
		if($command[0]=="CREATE" and $command[1]=="FILE") {
			$file = $protocol->getUnserializePHP();
			$new = $this->catalog->newEntry($file, $command[2]);
			if($file->getType()==Catalog::TYPE_FILE) {
				$storage = Storage::fromId($this->pdo, $this->partition->getStorageId());
				$storage->prepare($this->partition, $new->getVersions()->getLatest());
				$protocol->getRaw($storage);
			}
			
			$protocol->sendSerializePHP($new);
		return;
		}
	}
	
	public function onQuit() {
		
	}
}
