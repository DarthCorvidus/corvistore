<?php
class RunnerServer implements Runner, MessageListener {
	private $conn;
	private $clientId;
	private $queue;
	private $mode;
	private $pdo;
	private $protocol;
	function __construct($conn, int $clientId) {
		// Database connections need to be refreshed after forking.
		$this->pdo = Shared::getEPDO();
		$this->conn = $conn;
		$this->clientId = $clientId;
		$this->queue = new SysVQueue(ftok(__DIR__, "a"));
		$this->queue->addListener(Signal::get(), $this);
		$this->mode = NULL;
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
	}
	
	public function run() {
		#$this->runInitial();
		#$protocol = new \Net\Protocol($this->conn);
		#$protocol->sendOK();
		#$protocol->addProtocolListener($this->mode);
		#$protocol->listen();
		#fclose($this->conn);
		echo "Start loop for client ".$this->clientId.PHP_EOL;
		$auth = $this->protocol->getMessage();
		$explode = explode(" ", $auth);
		try {
			if($explode[0]=="admin") {
				$mode = new ModeAdmin($this->pdo, $this->protocol, $explode[1]);
				$mode->run();
			}
		} catch (InvalidArgumentException $e) {
			$this->protocol->sendError($e->getMessage());
		}
		/*
		while(true) {
			$command = $this->protocol->getCommand();
			$this->protocol->sendMessage("Sent: ".$command);
			if($command=="count") {
				for($i=0;$i<25;$i++) {
					$this->protocol->sendMessage("Count ".$i);
					sleep(1);
				}
			}
			if($command=="quit") {
				exit();
			}
		}
		 * 
		 */
	}
}
