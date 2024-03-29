<?php
class ModeNode implements \Net\ProtocolListener {
	private $pdo;
	private $catalog;
	private $quit = FALSE;
	private $conn;
	private $partition;
	function __construct(EPDO $pdo, string $node, $conn) {
		$this->pdo = $pdo;
		$this->node = Node::authenticate($this->pdo, $node);
		$this->catalog = new Catalog($this->pdo, $this->node);
		$this->partition = $this->node->getPolicy()->getPartition();
	}
	
	public function onCommand(string $command, \Net\Protocol $protocol) {
		$exp = explode(" ", $command);
		if(count($exp)==1) {
			$this->handleOne($command, $protocol);
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
	
	public function handleOne($command, \Net\Protocol $protocol) {
		if($command=="REPORT") {
			$report["files"] = $this->pdo->result("select count(dc_id) from d_catalog where dnd_id = ? and dc_id in (select dc_id from d_version where dvs_type = ?)", array($this->node->getId(), Catalog::TYPE_FILE));
			$params[] = $this->node->getId();
			$params[] = 1;
			$report["occupancy"] = $this->pdo->result("select sum(dvs_size) from d_catalog JOIN d_version USING (dc_id) JOIN n_version2basic USING (dvs_id) WHERE dnd_id = ? and dvs_stored = ?", $params);
			$report["oldest"] = $this->pdo->result("select min(dvs_created_epoch) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ?", array($this->node->getId()));
			$report["newest"] = $this->pdo->result("select max(dvs_created_epoch) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ?", array($this->node->getId()));
			$protocol->sendSerializePHP($report);
			return;
		}

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
			return;
		}
		if($command[0]=="UPDATE" and $command[1]=="FILE") {
			$file = $protocol->getUnserializePHP();
			$entry = $protocol->getUnserializePHP();
			$updated = $this->catalog->updateEntry($entry, $file);
			if($file->getType()== Catalog::TYPE_FILE) {
				$storage = Storage::fromId($this->pdo, $this->partition->getStorageId());
				$storage->prepare($this->partition, $updated->getVersions()->getLatest());
				$protocol->getRaw($storage);
			}
		}
		if($command[0]=="REPORT") {
			// Report for the root directory.
			if($command[1]=="/") {
				$entries = $this->catalog->getEntries(0);
				$protocol->sendSerializePHP($entries);
			return;
			}
			/*
			 * Report for a directory; get parent first, then entries below
			 * parent.
			 */
			if(substr($command[1], -1)=="/") {
				$parent = $this->catalog->getEntryByPath($this->node, substr($command[1], 0, -1));
				$entries = $this->catalog->getEntries($parent->getId());
				$protocol->sendSerializePHP($entries);
			return;
			}
			/*
			 * Report for single file
			 */
			$entry = $this->catalog->getEntryByPath($this->node, $command[1]);
			$protocol->sendSerializePHP($entry);
		return;
		}
	return "Invalid command.";
	}
	
	public function handleThree(array $command, \Net\Protocol $protocol) {
		if($command[0]=="GET" and strtoupper($command[1])=="CATALOG") {
			$entries = $this->catalog->getEntries($command[2]);
			$protocol->sendSerializePHP($entries);
		return;
		}

		if($command[0]=="GET" and $command[1]=="PATH") {
			$entry = $this->catalog->getEntryByPath($this->node, $command[2]);
			$protocol->sendSerializePHP($entry);
		return;
		}
		/*
		 * TODO: This is plain wrong, as it assumes that the version (file) is
		 * on the same storage as the partition of the node's policy. This is
		 * true for the current state of the product, however, in the long run
		 * there will be data migration/move to other partitions/storages, and
		 * there will be different types of storage as well.
		 */
		if($command[0]=="GET" and $command[1]=="VERSION") {
			$storage = Storage::fromId($this->pdo, $this->partition->getStorageId());
			try {
				$storage->sendFile($protocol, $command[2]);
			} catch (RuntimeException $e) {
				$protocol->sendError($e->getMessage());
				throw $e;
			}
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
