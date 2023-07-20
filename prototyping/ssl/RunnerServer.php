<?php
class RunnerServer implements Runner, MessageListener, SignalHandler {
	private $socket;
	private $clientId;
	function __construct($msgsock, int $clientId) {
		$this->clientId = $clientId;
		$this->socket = $msgsock;
		$this->protocol = new \Net\ProtocolBase($msgsock);
		$signal = Signal::get();
		$signal->clearSignal(SIGTERM);
		//$signal->addSignalHandler(SIGTERM, $this);
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
			$command = trim(fgets($this->socket));
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
