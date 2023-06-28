<?php
class RunnerServer implements Runner, MessageListener {
	private $conn;
	private $clientId;
	private $queue;
	function __construct($conn, int $clientId) {
		$this->conn = $conn;
		$this->clientId = $clientId;
		$this->queue = new SysVQueue(ftok(__DIR__, "a"));
		$this->queue->addListener(Signal::get(), $this);
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
		return $this->conn;
	}
	
	private function write($message) {
		socket_write($this->conn, $message, strlen($message));
	}
	
	public function run() {
		echo "Start loop for client ".$this->clientId.PHP_EOL;
		do {
			$read[] = $this->conn;
			$write = NULL;
			$except = NULL;
			if(stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				#if(socket_last_error($this->conn)!==0) {
				#	echo "socket_select() failed: ".socket_strerror(socket_last_error($this->conn)).PHP_EOL;
				#}
				continue;
			}
			if(false === ($buf = fgets($this->conn))) {
				throw new Exception("fread failed");
				return;
			}
			if(!$buf = trim($buf)) {
				continue;
			}
			if($buf == 'quit') {
				echo $this->clientId." requested end of connection".PHP_EOL;
				fclose($this->conn);
				return;
			}
			$talkback = sprintf("Client %d said %s", $this->clientId, $buf).PHP_EOL;
			fwrite($this->conn, $talkback, strlen($talkback));
		} while(true);
	}
}
