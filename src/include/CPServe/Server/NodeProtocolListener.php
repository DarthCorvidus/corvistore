<?php
namespace Server;
class NodeProtocolListener implements \Net\ProtocolReactiveListener {
	private $clientId;
	private $node;
	private $pdo;
	private $createId;
	private $catalog;
	private $fileAction;
	public function __construct(\EPDO $pdo, int $clientId, \Node $node) {
		$this->clientId = $clientId;
		$this->node = $node;
		$this->pdo = $pdo;
		$this->catalog = new \Catalog($this->pdo, $this->node);
	}
	public function onCommand(\Net\ProtocolReactive $protocol, string $command) {
		echo "Received ".$command.PHP_EOL;
		$exp = explode(" ", $command, 3);
		if(count($exp)==1) {
			$this->handleOne($protocol, $command);
		}
		
		if(count($exp)==2) {
			$this->handleTwo($protocol, $exp);
		}

		if(count($exp)==3) {
			$this->handleThree($protocol, $exp);
		}
	}

	private function handleOne(\Net\ProtocolReactive $protocol, string $command) {
		if($command == "report") {
			$report["files"] = $this->pdo->result("select count(dc_id) from d_catalog where dnd_id = ? and dc_id in (select dc_id from d_version where dvs_type = ?)", array($this->node->getId(), \Catalog::TYPE_FILE));
			$params[] = $this->node->getId();
			$params[] = 1;
			$report["occupancy"] = $this->pdo->result("select sum(dvs_size) from d_catalog JOIN d_version USING (dc_id) JOIN n_version2basic USING (dvs_id) WHERE dnd_id = ? and dvs_stored = ?", $params);
			$report["oldest"] = $this->pdo->result("select min(dvs_created_epoch) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ?", array($this->node->getId()));
			$report["newest"] = $this->pdo->result("select max(dvs_created_epoch) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ?", array($this->node->getId()));
			$protocol->sendSerialize($report);
		return;
		}

		if($command == "quit") {
			echo "Terminating worker for ".$this->clientId." with PID ".posix_getpid().PHP_EOL;
			exit();
		}
	}
	
	private function handleTwo(\Net\ProtocolReactive $protocol, array $command) {
		if($command[0]=="report") {
			// Report for the root directory.
			if($command[1]=="/") {
				$entries = $this->catalog->getEntries(0);
				$protocol->sendSerialize($entries);
			return;
			}
			/*
			 * Report for a directory; get parent first, then entries below
			 * parent.
			 */
			if(substr($command[1], -1)=="/") {
				$parent = $this->catalog->getEntryByPath($this->node, substr($command[1], 0, -1));
				$entries = $this->catalog->getEntries($parent->getId());
				$protocol->sendSerialize($entries);
			return;
			}
			/*
			 * Report for single file
			 */
			$entry = $this->catalog->getEntryByPath($this->node, $command[1]);
			$protocol->sendSerialize($entry);
		return;
		}
		if($command[0]=="send" && $command[1]=="ok") {
			$protocol->sendOK();
		}
	}
	
	private function handleThree(\Net\ProtocolReactive $protocol, array $command) {
		if($command[0]=="GET" and strtoupper($command[1])=="CATALOG") {
			$entries = $this->catalog->getEntries($command[2]);
			$protocol->sendSerialize($entries);
		return;
		}
		if($command[0]=="CREATE" and $command[1]=="FILE") {
			$protocol->expect(\Net\ProtocolReactive::SERIALIZED_PHP);
			#$this->createId = $command[2];
			$this->fileAction = "CREATE";
		}

	}
	
	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		echo "Client ".$this->clientId." disconnected, exiting worker with ".posix_getpid().PHP_EOL;
		exit();
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $command) {
		
	}

	public function onSerialized(\Net\ProtocolReactive $protocol, $unserialized) {
		echo "Received serialized ".get_class($unserialized).PHP_EOL;
		if($this->fileAction!==NULL && get_class($unserialized)=="File") {
			$this->onSerializedFile($protocol, $unserialized, $this->fileAction);
			$this->fileAction = NULL;
		}
	}
	
	private function onSerializedFile(\Net\ProtocolReactive $protocol, \File $file, string $action) {
		if($file->getType()== \Catalog::TYPE_DIR && $action=="CREATE") {
			echo "new entry ".$file->getPath().PHP_EOL;
			$entry = $this->catalog->newEntry($file);
			#$protocol->sendSerialize($entry);
		}
	}
	

	public function onOk(\Net\ProtocolReactive $protocol) {
		
	}

}