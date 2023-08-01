<?php
class RunnerWorker implements Runner, MessageListener, SignalHandler {
	private $socket;
	private $clientId;
	private $hub;
	private $protocol;
	function __construct($msgsock, int $clientId) {
		$this->clientId = $clientId;
		$this->socket = $msgsock;
		stream_set_blocking($this->socket, FALSE);
		$this->protocol = new \Net\ProtocolReactive(new SSLProtocolListener($this->clientId));
		$signal = Signal::get();
		$signal->clearSignal(SIGTERM);
		$this->hub = new StreamHub();
		
		$this->hub->addClientStream("ipc", $this->clientId, $msgsock, $this->protocol);
		#$this->writeBuffer[$name.":".$id] = "";
		#$this->sslProtocol[$name.":".$id] = new \Net\ProtocolReactive(new SSLProtocolListener($id));
		$this->protocol->sendMessage("Welcome to Test SSL Server 1.0");
		$this->protocol->sendMessage("(c) ACME Backup Software");
		$this->protocol->sendMessage("Connected on ".date("Y-m-d H:i:s")." as client ".$this->clientId);

	}
	
	function getQueue(): SysVQueue {
		return $this->queue;
	}
	
	function onMessage(\Message $message) {
		$this->write($message->getMessage()).PHP_EOL;
	}
	
	function getId() {
		return $this->clientId;
	}
	
	public function getConnection() {
		return $this->ipcClient;
	}
	
	private function write($message) {
		socket_write($this->ipcClient, $message, strlen($message));
	}
	
	public function run() {
		echo "Start worker for client ".$this->clientId.PHP_EOL;
		$this->hub->listen();
	return;
		
		#if (! stream_socket_enable_crypto ($this->conn, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT )) {
		#	exit();
		#}
		do {
			$read = array();
			$read["main"] = $this->socket;
			$write = NULL;
			$except = NULL;
			if(stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				#if(socket_last_error($this->conn)!==0) {
				#	echo "socket_select() failed: ".socket_strerror(socket_last_error($this->conn)).PHP_EOL;
				#}
				continue;
			}
			if(!isset($read["main"])) {
				continue;
			}
			
			if($command=="") {
				continue;
			}
			echo sprintf("Command via IPC from %d: %s", $this->clientId, $command).PHP_EOL;
			if($command=="quit") {
				echo "Quitting worker (via command)...".PHP_EOL;
				exit();
			}
		} while(true);
	}

	public function onSignal(int $signal, array $info) {
		if($signal==SIGTERM) {
			echo "Quitting worker (via signal)...";
		}
	}
}
