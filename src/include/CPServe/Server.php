<?php
class Server implements ProcessListener, SignalHandler, Net\HubServerListener, \Net\ProtocolAsyncListener {
	private $hub;
	private $workerProcess = array();
	private $pdo;
	private $authProt = array();
	private $authMode = array();
	private $authFail = array();
	private $workers = array();
	function __construct(EPDO $pdo) {
		set_time_limit(0);
		ob_implicit_flush();
		pcntl_async_signals(true);
		$signal = Signal::get();
		$this->pdo = $pdo;
		#$signal->addSignalHandler(SIGINT, $this);
		#$signal->addSignalHandler(SIGTERM, $this);
		if(file_exists(Shared::getIPCSocket())) {
			unlink(Shared::getIPCSocket());
		}
		$ipcServer = stream_socket_server("unix://".Shared::getIPCSocket(), $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
		$this->hub = new StreamHub();
		$this->hub->addServer("ipc", $ipcServer, $this);
	}
	
	function onSignal(int $signal, array $info) {
		if($signal==SIGINT or $signal==SIGTERM) {
			socket_close($this->socket);
			echo "Exiting.".PHP_EOL;
			exit();
		}
	}

	function run() {
		$runner = new \Server\RunnerSSL();
		$sslProcess = new Process($runner);
		$sslProcess->addProcessListener($this);
		$sslProcess->run();
		
		$this->hub->listen();
	}

	public function onEnd(Process $process) {
		$name = $process->getRunnerName();
		/*
		 * If the SSL fork crashes, quit here, end the workers.
		 */
		if($name=="RunnerSSL") {
			foreach($this->workerProcess as $key => $value) {
				echo "Ending ".$value->getPid().PHP_EOL;
				$value->sigTerm();
			}
			exit(0);
		}
		/*
		 * If the client quits via "quit", quit is sent to the Worker via IPC,
		 * which will end as well. Clean up here.
		 */
		if($name=="WorkerAdmin") {
			$clientId = $process->getRunner()->getId();
			echo "Removing WorkerAdmin #".$clientId.PHP_EOL;
			unset($this->workerProcess[$clientId]);
			unset($this->workers[$clientId]);
			Signal::get()->clearHandler($process);
		}
	}

	public function onStart(Process $process) {
		if($process->getRunner() instanceof RunnerServer) {
			$id = $process->getRunner()->getId();
			echo "Thread for client ".$id." spawned.".PHP_EOL;
		}
	}

	public function onConnect(string $name, int $id, $newClient) {
		echo "Connection from ".$name." ".$id.PHP_EOL;
		#echo "New IPC connection - forking off...";
		#$this->hub->detach($name, $id);
		#$worker = new RunnerWorker($newClient, $id);
		#$process = new Process($worker);
		#$process->run();
		#echo "forked off with PID ".$process->getPid().PHP_EOL;
	}
	
	public function onDetach($name, $id) {
		$key = $name.":".$id;
		if($name!="ipc") {
			return;
		}
		$process = new Process($this->workers[$id]);
		$process->run();
		unset($this->authFail[$key]);
		unset($this->authProt[$key]);
		unset($this->authMode[$key]);
	}
	
	public function hasClientListener(string $name, int $id): bool {
		return true;
	}
	
	public function getClientListener(string $name, int $id): \Net\HubClientListener {
		$protocol = new \Net\ProtocolAsync($this);
		$this->authProt[$name.":".$id] = $protocol;
		$this->authFail[$name.":".$id] = 0;
		$this->authMode[$name.":".$id] = NULL;
	return $protocol;
	}
	
	public function hasClientNamedListener(string $name, int $id): bool {
		return false;
	}
	
	public function getClientNamedListener(string $name, int $id): \Net\HubClientNamedListener {
		;
	}

	private function endHandshake(string $key) {
		$kexp = explode(":", $key);
		$name = $kexp[0];
		$id = $kexp[1];
		$this->hub->close($name, $id);
		unset($this->authFail[$key]);
		unset($this->authProt[$key]);
		unset($this->authMode[$key]);
	}
	
	public function onCommand(\Net\ProtocolAsync $protocol, string $command) {
		$key = array_search($protocol, $this->authProt, TRUE);
		$kexp = explode(":", $key);
		$name = $kexp[0];
		$id = $kexp[1];
		echo $command.PHP_EOL;
		if($command=="quit") {
			$this->endHandshake($key);
		return;
		}
		$exp = explode(" ", $command);
		if(count($exp)!=2) {
			echo "Malformed mode select from ".$key.PHP_EOL;
			$this->endHandshake($key);
		return;
		}
		
		if($this->authMode[$key]==NULL && $exp[0]=="mode" && !in_array($exp[1], array("admin", "node", TRUE))) {
			echo "Unknown mode from ".$key.PHP_EOL;
			$this->endHandshake($key);
		return;
		}
		if($this->authMode[$key]==NULL && $exp[0]=="mode" && in_array($exp[1], array("admin", "node", TRUE))) {
			$this->authMode[$key] = $exp[1];
			#$protocol->sendOK();
			$protocol->expect(\Net\ProtocolAsync::COMMAND);
		return;
		}
		if($this->authMode[$key]!=NULL && $exp[0]=="authenticate") {
			if($this->authMode[$key]=="admin") {
				echo "Authenticating admin...";
				try {
					$user = User::authenticate($this->pdo, $exp[1]);
					echo $user->getName()." authenticated!".PHP_EOL;
					$protocol->sendOK();
					$msgsock = $this->hub->getStream($name, $id);
					$this->workers[$id] = new Server\WorkerAdmin($msgsock, $id, $user->getId());
					$this->hub->detach($name, $id);
					return;
				} catch (Exception $ex) {
					echo "Authentication failed!".PHP_EOL;
					$this->endHandshake($key);
				return;
				}
			}
			if($this->authMode[$key]=="node") {
				echo "Authenticating node...".PHP_EOL;
				try {
					$node = Node::authenticate($this->pdo, $exp[1]);
					echo $node->getName()." authenticated!".PHP_EOL;
					$protocol->sendOK();
					$msgsock = $this->hub->getStream($name, $id);
					$this->workers[$id] = new Server\WorkerNode($msgsock, $id, $node->getId());
					$this->hub->detach($name, $id);
					return;
				} catch (Exception $ex) {
					echo "Authentication failed!".PHP_EOL;
					$this->endHandshake($key);
				return;
				}
			}
		}
		$this->endHandshake($key);
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol) {
		$key = array_search($protocol, $this->authProt, TRUE);
		unset($this->authFail[$key]);
		unset($this->authProt);
		unset($this->authMode);
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message) {
		
	}

	public function onOk(\Net\ProtocolAsync $protocol) {
		
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, $unserialized) {
		
	}

}
