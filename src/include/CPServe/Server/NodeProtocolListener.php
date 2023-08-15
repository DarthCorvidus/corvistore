<?php
namespace Server;
class NodeProtocolListener implements \Net\ProtocolReactiveListener {
	private $clientId;
	private $node;
	private $pdo;
	public function __construct(\EPDO $pdo, int $clientId, \Node $node) {
		$this->clientId = $clientId;
		$this->node = $node;
		$this->pdo = $pdo;
	}
	public function onCommand(\Net\ProtocolReactive $protocol, string $command) {
		echo "Received ".$command.PHP_EOL;
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

	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		echo "Client ".$this->clientId." disconnected, exiting worker with ".posix_getpid().PHP_EOL;
		exit();
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $command) {
		
	}

	public function onSerialized(\Net\ProtocolReactive $protocol, $unserialized) {
		
	}

	public function onOk(\Net\ProtocolReactive $protocol) {
		
	}

}