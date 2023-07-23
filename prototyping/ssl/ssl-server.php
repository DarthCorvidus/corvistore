#!/usr/bin/php
<?php
include __DIR__."/../../vendor/autoload.php";
include __DIR__."/RunnerWorker.php";
include __DIR__."/RunnerSSL.php";
class Server implements ProcessListener, MessageListener, SignalHandler {
	private $socket;
	private $clients = array();
	private $queue;
	private $ipcServer;
	private $ipcClients = array();
	function __construct() {
		set_time_limit(0);
		ob_implicit_flush();
		pcntl_async_signals(true);
		$signal = Signal::get();
		#$signal->addSignalHandler(SIGINT, $this);
		#$signal->addSignalHandler(SIGTERM, $this);
		$this->queue = new SysVQueue(ftok(__DIR__, "a"));
		$this->queue->addListener($signal, $this);
		$address = '127.0.0.1';
		$port = 4096;
		$context = stream_context_create();

		if(file_exists("ssl-server.socket")) {
			unlink("ssl-server.socket");
		}
		$this->ipcServer = stream_socket_server("unix://ssl-server.socket", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
		#if (! stream_socket_enable_crypto ($this->socket, true, STREAM_CRYPTO_METHOD_TLS_SERVER )) {
		#	exit();
		#}

		/*
		if (($this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
			throw new RuntimeException(sprintf("Socket creation failed: %s", socket_strerror(socket_last_error())));
		}

		if (@socket_bind($this->socket, $address, $port) === false) {
			throw new RuntimeException(sprintf("Socket bind with %s:%d failed: %s", $address, $port, socket_strerror(socket_last_error())));
		}

		if (@socket_listen($this->socket, 5) === false) {
			throw new RuntimeException(sprintf("Socket listen with %s:%d failed: %s", $address, $port, socket_strerror(socket_last_error())));
		}
		 * 
		 */
	}
	
	function onSignal(int $signal, array $info) {
		if($signal==SIGINT or $signal==SIGTERM) {
			socket_close($this->socket);
			echo "Exiting.".PHP_EOL;
			exit();
		}
	}
	
	private function onConnect($msgsock) {
		$this->clients["ssl:".$this->clientCount] = $msgsock;
		#$runner = new RunnerServer($this->clientCount);
		#$process = new Process($runner);
		#$process->addProcessListener($this);
		#$process->run();
		$this->clientCount++;
	}
	
	function onMessage(\Message $message) {
		if($message->getMessage()=="status") {
			$answer  = "";
			$answer .= "Clients: ".count($this->clients).PHP_EOL;
			$this->queue->sendHyperwave($answer, 1, $message->getSourcePID());
		}
	}
	
	private function streamSelect(): array {
		$read = array();
		$read["mainServer"] = $this->socket;
		$read["mainIPC"] = $this->ipcServer;
		foreach($this->ipcClients as $key => $value) {
			$read[$key] = $value;
		}
		foreach($this->clients as $key => $value) {
			$read[$key] = $value;
		}
		if(@stream_select($read, $write, $except, $tv_sec = 5) < 1) {
			//pcntl_sigprocmask(SIG_UNBLOCK, array(SIGCHLD));
			#$error = socket_last_error($this->socket);
			#if($error!==0) {
			#	echo sprintf("socket_select() failed: %d %s", $error, socket_strerror($error)).PHP_EOL;
			#}
			return array();
		}
	return $read;
	}

	private function newServerClient() {
		echo "A new connection has occurred.".PHP_EOL;
		if (($msgsock = stream_socket_accept($this->socket)) === false) {
			echo "socket_accept() failed: ".socket_strerror(socket_last_error($this->socket)).PHP_EOL;
			#stream_set_blocking($msgsock, TRUE);
			return;
		}
		echo "New connection has been accepted.".PHP_EOL;
		stream_set_blocking($msgsock, true);
		if (! stream_socket_enable_crypto ($msgsock, true, STREAM_CRYPTO_METHOD_TLS_SERVER )) {
			exit();
		}
		$this->clients["ssl:".$this->clientCount] = $msgsock;
		$runner = new RunnerServer($this->clientCount);
		$process = new Process($runner);
		$process->addProcessListener($this);
		$process->run();
		$this->sslProtocol[$this->clientCount] = new \Net\ProtocolBase($msgsock);
		$this->sslProtocol[$this->clientCount]->sendMessage("Connected as client ".$this->clientCount);
		$this->clientCount++;
	}
	
	private function newIpcClient() {
		echo "A new IPC connection has occurred.".PHP_EOL;
		if (($msgsock = stream_socket_accept($this->ipcServer)) === false) {
			echo "IPC: socket_accept() failed: ".socket_strerror(socket_last_error($this->socket)).PHP_EOL;
			#stream_set_blocking($msgsock, TRUE);
			return;
		}
		stream_set_blocking($msgsock, TRUE);
		$clientId = IntVal::uint64LE()->getValue(fread($msgsock, 8));
		$this->ipcClients["ipc:".$clientId] = $msgsock;
		
		echo "New IPC connection has been accepted.".PHP_EOL;
	}
	
	function run() {
		$runner = new RunnerSSL();
		$sslProcess = new Process($runner);
		$sslProcess->addProcessListener($this);
		$sslProcess->run();
		$clients = array();
		$i = 0;
		do {
			$read = array();
			$read["mainIPC"] = $this->ipcServer;
			foreach($this->ipcClients as $key => $value) {
				$read[$key] = $value;
			}
			if(@stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				//pcntl_sigprocmask(SIG_UNBLOCK, array(SIGCHLD));
				#$error = socket_last_error($this->socket);
				#if($error!==0) {
				#	echo sprintf("socket_select() failed: %d %s", $error, socket_strerror($error)).PHP_EOL;
				#}
				continue;
			}
			if(isset($read["mainIPC"])) {
				echo "A new IPC connection has occurred.".PHP_EOL;
				if (($msgsock = stream_socket_accept($this->ipcServer)) === false) {
					echo "socket_accept() failed: ".socket_strerror(socket_last_error($this->socket)).PHP_EOL;
					#stream_set_blocking($msgsock, TRUE);
					return;
				}
				$clientId = IntVal::uint64LE()->getValue(fread($msgsock, 8));
				echo "New connection for ".$clientId." has been accepted.".PHP_EOL;
				$this->ipcClients[$clientId] = $msgsock;
				$this->workers[$clientId] = new RunnerServer($msgsock, $clientId);
				$this->workerProcess[$clientId] = new Process($this->workers[$clientId]);
				$this->workerProcess[$clientId]->addProcessListener($this);
				$this->workerProcess[$clientId]->run();
				echo "Forked off worker process with pid ".$this->workerProcess[$clientId]->getPid().PHP_EOL;
			}
		} while(TRUE);
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
		if($name=="RunnerServer") {
			echo "Removing worker".PHP_EOL;
			$clientId = $process->getRunner()->getId();
			unset($this->workers[$clientId]);
			unset($this->workerProcess[$clientId]);
			unset($this->ipcClients[$clientId]);
			Signal::get()->clearHandler($process);
			
		}
	}

	public function onStart(Process $process) {
		if($process->getRunner() instanceof RunnerServer) {
			$id = $process->getRunner()->getId();
			echo "Thread for client ".$id." spawned.".PHP_EOL;
		}
	}
}

$certfiles[] = __DIR__."/server.crt";
$certfiles[] = __DIR__."/server.key";
$certfiles[] = __DIR__."/ca.crt";

foreach($certfiles as $key => $value) {
	if(!file_exists($value)) {
		echo "Necessary certificate/key file ".$value." does not exist.".PHP_EOL;
	}
}

$server = new Server();
$server->run();