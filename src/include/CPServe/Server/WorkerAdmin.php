<?php
namespace Server;
class WorkerAdmin implements \Runner, \SignalHandler {
	private $socket;
	private $clientId;
	private $hub;
	private $protocol;
	private $user;
	private $pdo;
	function __construct($msgsock, int $clientId, int $userId) {
		$this->clientId = $clientId;
		$this->pdo = \Shared::getEPDO();
		$this->user = \User::fromId($this->pdo, $userId);
		$this->socket = $msgsock;
		stream_set_blocking($this->socket, FALSE);
		$this->protocol = new \Net\ProtocolReactive(new AdminProtocolListener($this->pdo, $this->clientId, $this->user));
		$signal = \Signal::get();
		$signal->clearSignal(SIGTERM);
		$this->hub = new \StreamHub();
		
		$this->hub->addClientStream("ipc", $this->clientId, $msgsock, $this->protocol);
		#$this->writeBuffer[$name.":".$id] = "";
		#$this->sslProtocol[$name.":".$id] = new \Net\ProtocolReactive(new SSLProtocolListener($id));
		$this->protocol->sendMessage("Welcome to Test SSL Server 1.0");
		$this->protocol->sendMessage("(c) ACME Backup Software");
		$this->protocol->sendMessage("Connected on ".date("Y-m-d H:i:s")." as client ".$this->clientId);

	}

	function getId() {
		return $this->clientId;
	}
	
	public function getConnection() {
		return $this->ipcClient;
	}
	
	public function run() {
		echo "Start worker for client ".$this->clientId.PHP_EOL;
		$this->hub->listen();
	return;
	}

	public function onSignal(int $signal, array $info) {
		if($signal==SIGTERM) {
			echo "Quitting worker (via signal)...";
		}
	}
}
