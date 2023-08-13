#!/usr/bin/php
<?php
include __DIR__."/../../vendor/autoload.php";
include __DIR__."/RunnerWorker.php";
include __DIR__."/RunnerSSL.php";
include __DIR__."/SSLProtocolListener.php";
class Server implements ProcessListener, SignalHandler, Net\HubServerListener {
	private $hub;
	private $workerProcess = array();
	function __construct() {
		set_time_limit(0);
		ob_implicit_flush();
		pcntl_async_signals(true);
		$signal = Signal::get();
		#$signal->addSignalHandler(SIGINT, $this);
		#$signal->addSignalHandler(SIGTERM, $this);
		if(file_exists(__DIR__."/ssl-server.socket")) {
			unlink(__DIR__."/ssl-server.socket");
		}
		$ipcServer = stream_socket_server("unix://".__DIR__."/ssl-server.socket", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
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
		$runner = new RunnerSSL();
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
		if($name=="RunnerServer") {
			echo "Removing worker".PHP_EOL;
			$clientId = $process->getRunner()->getId();
			unset($this->workerProcess[$clientId]);
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
		echo "New IPC connection - forking off...";
		$this->hub->detach($name, $id);
		$worker = new RunnerWorker($newClient, $id);
		$process = new Process($worker);
		$process->run();
		echo "forked off with PID ".$process->getPid().PHP_EOL;
	}
	
	public function hasClientListener(string $name, int $id): bool {
		return false;
	}
	
	public function getClientListener(string $name, int $id): \Net\HubClientListener {
		;
	}
	
	public function hasClientNamedListener(string $name, int $id): bool {
		return false;
	}
	
	public function getClientNamedListener(string $name, int $id): \Net\HubClientNamedListener {
		;
	}
	/*
	public function onRead(string $name, int $id, $stream) {
		echo "This should not get called.".PHP_EOL;
		#echo "New IPC activity: ".PHP_EOL;
		#$command = $this->ipcProtocol[$id]->getCommand();
		#echo $command.PHP_EOL;
		#$this->ipcProtocol[$id]->sendMessage("IPC message: received".$command);
	}

	public function onWrite(string $name, int $id, $stream) {
		
	}
	 * 
	 */

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