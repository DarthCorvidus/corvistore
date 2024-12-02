<?php
use plibv4\process\TimeshareObserver;
use Net\AsyncStream;
class Server implements SignalHandler, TimeshareObserver {
	private $hub;
	private $workerProcess = array();
	private $pdo;
	private $authProt = array();
	private $authMode = array();
	private $authFail = array();
	private $workers = array();
	private int $clientCount = 0;
	private \Idle $idle;
	private plibv4\process\Timeshare $ts;
	private Server\TaskServer $workerServer;
	private Server\Input $input;
	private array $running = [];
	private array $allowedIdle = [Idle::class, Server\TaskServer::class, \Server\Input::class];
	function __construct(EPDO $pdo) {
		set_time_limit(0);
		ob_implicit_flush();
		pcntl_async_signals(true);
		$signal = Signal::get();
		$this->pdo = $pdo;
		/**
		 * Add a delay of 50 milliseconds, so the server does not gobble up all
		 * available resources.
		 */
		$this->idle = new Idle(0, 50);
		$this->ts = new plibv4\process\Timeshare();
		$this->ts->addTimeshareObserver($this);
		$this->workerServer = new Server\TaskServer();
		$this->input = new Server\Input($pdo, $this->ts);
		$this->ts->addTask($this->workerServer);
		$this->ts->addTask($this->idle);
		$this->ts->addTask($this->input);
		#$signal->addSignalHandler(SIGINT, $this);
		#$signal->addSignalHandler(SIGTERM, $this);
		if(file_exists(Shared::getIPCSocket())) {
			unlink(Shared::getIPCSocket());
		}
		$ipcServer = stream_socket_server("unix://".Shared::getIPCSocket(), $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
		#$this->hub = new StreamHub();
		#$this->hub->addServer("ipc", $ipcServer, $this);
	}
	
	function onSignal(int $signal, array $info) {
		if($signal==SIGINT or $signal==SIGTERM) {
			socket_close($this->socket);
			echo "Exiting.".PHP_EOL;
			exit();
		}
	}

	function run() {
		/*
		$runner = new \Server\RunnerSSL();
		$sslProcess = new Process($runner);
		$sslProcess->addProcessListener($this);
		$sslProcess->run();
		
		$this->hub->listen();
		 * 
		 */
		$this->ts->run();
		echo "Server shutdown.".PHP_EOL;
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

	public function onAdd(\plibv4\process\Scheduler $scheduler, \plibv4\process\Task $task): void {
		$className = $task::class;
		if(!in_array($className, $this->allowedIdle)) {
			echo "Pausing idle for ".$className.PHP_EOL;
			$scheduler->pause($this->idle);
		}
		if(!isset($this->running[$className])) {
			$this->running[$className] = 0;
		}
		$this->running[$className]++;
	}

	public function onError(\plibv4\process\Scheduler $scheduler, \plibv4\process\Task $task, \Exception $e, int $step): void {
		
	}

	public function onPause(\plibv4\process\Scheduler $scheduler, \plibv4\process\Task $task): void {
		
	}
	
	public function onRemove(\plibv4\process\Scheduler $scheduler, \plibv4\process\Task $task, int $step): void {
		if(!$scheduler->hasTask($this->idle)) {
			return;
		}
		/*
		 * If the last client disconnects, reactivate Idle process
		 */
		$classname = $task::class;
		$this->running[$classname]--;
		if($this->running[$classname]<=0) {
			unset($this->running[$classname]);
		}
		
		foreach($this->running as $key => $value) {
			if(!in_array($key, $this->allowedIdle)) {
				echo $key." prevents idling.".PHP_EOL;
			return;
			}
		}
		echo "Resume idling".PHP_EOL;
		$scheduler->resume($this->idle);
	
		
		if($task instanceof AsyncStream) {
			$this->clientCount--;
		}
		
		/*
		 * Resume if clientCount. As Idle may vanish first on server shutdown,
		 * only end it if it is still there.
		 */
		if($this->clientCount==0 && $scheduler->hasTask($this->idle)) {
			#$scheduler->resume($this->idle);
		}
	}

	public function onResume(\plibv4\process\Scheduler $scheduler, \plibv4\process\Task $task): void {
		
	}

	public function onStart(\plibv4\process\Scheduler $scheduler, \plibv4\process\Task $task): void {
		
	}
}
