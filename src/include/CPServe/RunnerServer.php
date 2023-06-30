<?php
class RunnerServer implements Runner, MessageListener {
	private $conn;
	private $clientId;
	private $queue;
	private $mode;
	private $pdo;
	function __construct($conn, int $clientId) {
		$databasePath = "/var/lib/crow-protect/crow-protect.sqlite";
		$shared = new Shared();
		$shared->useSQLite($databasePath);
		$this->pdo = $shared->getEPDO();
		$this->conn = $conn;
		$this->clientId = $clientId;
		$this->queue = new SysVQueue(ftok(__DIR__, "a"));
		$this->queue->addListener(Signal::get(), $this);
		$this->mode = NULL;
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
		fwrite($this->conn, $message, strlen($message));
	}
	
	private function authenticate($trimmed) {
		$exp = explode(" ", $trimmed);
		if(!in_array(strtoupper($exp[0]), array("NODE", "ADMIN", "QUIT", "TEST"))) {
			$this->write("CP001E: Select mode NODE, ADMIN or end connection QUIT".PHP_EOL);		
		}
		if($this->mode == NULL and strtoupper($exp[0])=="ADMIN") {
			try {
				$this->mode = new ModeAdmin($this->pdo, $exp[1]);
				// echoing the conjoined string has to be removed here & with
				// NODE, but it is ok for debugging.
				echo sprintf("Client %d identified as ADMIN %s", $this->clientId, $exp[1]).PHP_EOL;
			} catch (Exception $e) {
				echo $e->getMessage();
				$this->write($e->getMessage());
				fclose($this->conn);
				exit(0);
			}
			return;
		}
		if(strtoupper($exp[0])=="NODE" and !isset($exp[1])) {
			$this->write("CP002E: missing node.".PHP_EOL);
			return;
		}
		
		if($this->mode==NULL and strtoupper($exp[0])=="NODE") {
			try {
				$this->mode = new ModeNode($this->pdo, $exp[1], $this->conn);
				echo sprintf("Client %d identified as NODE %s", $this->clientId, $exp[1]).PHP_EOL;
			} catch (Exception $e) {
				$this->write($e->getMessage());
				fclose($this->conn);
				exit(0);
			}
			return;
		}

		#if($this->mode==NULL and strtoupper($trimmed)=="ADMIN") {
		#	$this->mode = new ModeAdmin($this->pdo);
		#	echo sprintf("Client %d identified as 'ADMIN'", $this->clientId).PHP_EOL;
		#	return;
		#}
		if($this->mode==NULL and strtoupper($trimmed)=="TEST") {
			$this->mode = new ModeTest($this->clientId);
			echo sprintf("Client %d identified as 'TEST'", $this->clientId).PHP_EOL;
			return;
		}

	}
	
	public function runInitial() {
		echo "Start loop for client ".$this->clientId.PHP_EOL;
		do {
			$read[] = $this->conn;
			$write = NULL;
			$except = NULL;
			#if(@stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				#if(socket_last_error($this->conn)!==0) {
				#	echo "socket_select() failed: ".socket_strerror(socket_last_error($this->conn)).PHP_EOL;
				#}
			#	continue;
			#}
			if(false === ($buf = fgets($this->conn))) {
				echo "socket_read() failed: ".socket_strerror(socket_last_error($this->conn)).PHP_EOL;
				return;
			}
			$trimmed = trim($buf);
			echo "Client sent ".$trimmed.PHP_EOL;
			/**
			 * We select the mode here. NODE is a backup endpoint, CLIENT is an
			 * administrative endpoint.
			 */
			if($trimmed=="") {
				continue;
			}

			if($this->mode==NULL and strtoupper($trimmed)=="QUIT") {
				echo $this->clientId." requested end of connection".PHP_EOL;
				fclose($this->conn);
				return;
			}
			
			if($this->mode==NULL) {
				$this->authenticate($trimmed);
			}
			
			if($this->mode!=NULL) {
				echo "Breaking initial loop.".PHP_EOL;
				break;
			}
			
			if($this->mode==NULL) {
				continue;
			}
			
			if($this->mode!=NULL) {
				$this->mode->onServerMessage($trimmed);
				if($this->mode->isQuit()) {
					echo $this->clientId." requested end of connection".PHP_EOL;
					socket_close($this->conn);
					return;
				}
				continue;
			}
			$msg = sprintf("Unknown command: ".$buf);
			$talkback = sprintf("Client %d said %s", $this->clientId, $buf).PHP_EOL;
			socket_write($this->conn, $talkback, strlen($talkback));
		} while(true);
	}
	
	public function run() {
		$this->runInitial();
		$protocol = new \Net\Protocol($this->conn);
		$protocol->sendOK();
		$protocol->addProtocolListener($this->mode);
		$protocol->listen();
		fclose($this->conn);
	}
}
