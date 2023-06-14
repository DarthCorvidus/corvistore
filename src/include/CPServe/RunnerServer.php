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
		socket_write($this->conn, $message, strlen($message));
	}
	
	private function authenticate($trimmed) {
		$exp = explode(" ", $trimmed);
		if(!in_array(strtoupper($exp[0]), array("NODE", "ADMIN", "QUIT"))) {
			$this->write("CP001E: Select mode NODE, ADMIN or end connection QUIT".PHP_EOL);		
		}
		if(strtoupper($exp[0])=="ADMIN") {
			$this->mode = new ModeAdmin();
			return;
		}
		if(strtoupper($exp[0])=="NODE" and !isset($exp[1])) {
			$this->write("CP002E: missing node.".PHP_EOL);
			return;
		}
		
		if($this->mode==NULL and strtoupper($exp[0])=="NODE") {
			try {
				$this->mode = new ModeNode($this->pdo, $exp[1]);
				echo sprintf("Client %d identified as NODE %s", $this->clientId, $exp[1]).PHP_EOL;
			} catch (Exception $e) {
				$this->write($e->getMessage());
				socket_close($this->conn);
				exit(0);
			}
			return;
		}

		if($this->mode==NULL and strtoupper($trimmed)=="ADMIN") {
			$this->mode = new ModeAdmin($pdo);
			echo sprintf("Client %d identified as 'ADMIN'", $this->clientId).PHP_EOL;
			return;
		}
	}
	
	public function run() {
		$shared = new Shared();
		$shared->useSQLite("/var/lib/crow-protect/crow-protect.sqlite");
		$pdo = $shared->getEPDO();
		echo "Start loop for client ".$this->clientId.PHP_EOL;
		do {
			$read[] = $this->conn;
			$write = NULL;
			$except = NULL;
			if(@socket_select($read, $write, $except, $tv_sec = 5) < 1) {
				if(socket_last_error($this->conn)!==0) {
					echo "socket_select() failed: ".socket_strerror(socket_last_error($this->conn)).PHP_EOL;
				}
				continue;
			}
			if(false === ($buf = socket_read($this->conn, 2048, PHP_NORMAL_READ))) {
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
				socket_close($this->conn);
				return;
			}
			
			if($this->mode==NULL) {
				$this->authenticate($trimmed);
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
				
			if($buf == 'quit') {
				echo $this->clientId." requested end of connection".PHP_EOL;
				socket_close($this->conn);
				return;
			}
			if($buf == "sleep") {
				$message = "Going to sleep for 15 seconds!".PHP_EOL;
				$this->write($message);
				sleep(15);
				$this->write("Woke up.").PHP_EOL;
				continue;
			}
			
			if($buf == "status") {
				$this->queue->sendHyperwave("status", 1, posix_getppid());
			}
			
			if($buf == "help") {
				$message = "help - this help".PHP_EOL;
				$message .= "status - print status".PHP_EOL;
				$message .= "quit - disconnect".PHP_EOL;
				$message .= "sleep - sleep for 15 seconds".PHP_EOL;
				$this->write($message);
				continue;
			}
			$msg = sprintf("Unknown command: ".$buf);
			$talkback = sprintf("Client %d said %s", $this->clientId, $buf).PHP_EOL;
			socket_write($this->conn, $talkback, strlen($talkback));
		} while(true);
	}
}
