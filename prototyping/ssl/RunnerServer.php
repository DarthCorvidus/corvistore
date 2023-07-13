<?php
class RunnerServer implements Runner, MessageListener {
	private $conn;
	private $clientId;
	private $queue;
	private $protocol;
	function __construct($conn, int $clientId) {
		$this->conn = $conn;
		$this->clientId = $clientId;
		$this->queue = new SysVQueue(ftok(__DIR__, "a"));
		$this->queue->addListener(Signal::get(), $this);
		$this->protocol = new \Net\ProtocolBase($this->conn);
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
		$this->protocol->sendMessage("Connected as client ".$this->clientId);
		#if (! stream_socket_enable_crypto ($this->conn, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT )) {
		#	exit();
		#}

		do {
			$read = array();
			$read[] = $this->conn;
			$write = NULL;
			$except = NULL;
			if(stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				#if(socket_last_error($this->conn)!==0) {
				#	echo "socket_select() failed: ".socket_strerror(socket_last_error($this->conn)).PHP_EOL;
				#}
				continue;
			}
			$command = $this->protocol->getCommand();
			$talkback = sprintf("Client %d sent %s", $this->clientId, $command);
			echo $talkback.PHP_EOL;
			if($command=="help") {
				$help = "status - get status information".PHP_EOL;
				$help .= "sleep - sleep for 15 seconds";
				$this->protocol->sendMessage($help);
			continue;
			}
			
			if($command=="quit") {
				$this->protocol->sendMessage("Quitting.");
				echo $this->clientId." requested end of connection".PHP_EOL;
				
				
				fclose($this->conn);
				return;
			}
			if($command=="status") {
				$this->protocol->sendMessage("Connected as client ".$this->clientId);
			continue;
			}
			if($command=="sleep") {
				$this->protocol->sendMessage("Sleeping for 15 seconds.");
				sleep(15);
				$this->protocol->sendMessage("Woke up after 15 seconds");
			continue;
			}
			$this->protocol->sendMessage("You sent command: ".$command);
		} while(true);
	}
}
