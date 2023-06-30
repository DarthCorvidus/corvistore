<?php
class CPServe implements ProcessListener, MessageListener, SignalHandler {
	private $socket;
	private $clients = array();
	private $queue;
	private $pdo;
	function __construct(EPDO $pdo) {
		$this->pdo = $pdo;
		set_time_limit(0);
		ob_implicit_flush();
		pcntl_async_signals(true);
		$signal = Signal::get();
		$signal->addSignalHandler(SIGINT, $this);
		$signal->addSignalHandler(SIGTERM, $this);
		$this->queue = new SysVQueue(ftok(__DIR__, "a"));
		$this->queue->addListener($signal, $this);
		$address = '0.0.0.0';
		$port = 4096;
		$this->socket = @stream_socket_server($address.":".$port, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
		#stream_set_blocking($this->socket, TRUE);
		#stream_set_read_buffer($this->socket, 0);
		#stream_set_write_buffer($this->socket, 0);
		if($this->socket == FALSE) {
			throw new RuntimeException("Unable to bind to ".$address.":".$port.":".$errstr);
		}
	}
	
	function onSignal(int $signal, array $info) {
		if($signal==SIGINT or $signal==SIGTERM) {
			fclose($this->socket);
			echo "Exiting.".PHP_EOL;
			exit();
		}
	}
	
	private function onConnect($msgsock) {
		$this->clients[] = $msgsock;
		$keys = array_keys($this->clients, $msgsock);
		$runner = new RunnerServer($msgsock, $keys[0]);
		$process = new Process($runner);
		$process->addProcessListener($this);
		$process->run();
	}
	
	function onMessage(\Message $message) {
		if($message->getMessage()=="status") {
			$answer  = "";
			$answer .= "Clients: ".count($this->clients).PHP_EOL;
			$this->queue->sendHyperwave($answer, 1, $message->getSourcePID());
		}
	}
	
	function run() {
		do {
			pcntl_signal_dispatch();
			#Any activity on the main socket will spawn a new process.
			$read[] = $this->socket;
			$write = NULL;
			$except = NULL;
			if(@stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				#$error = socket_last_error($this->socket);
				#echo $error.PHP_EOL;
				#if($error!==0) {
				#	echo sprintf("socket_select() failed: %d %s", $error, socket_strerror($error)).PHP_EOL;
				#}
				continue;
			}
			echo "A new connection has occurred.".PHP_EOL;
			if (($msgsock = stream_socket_accept($this->socket)) === false) {
				echo "stream_socket_accept failed".PHP_EOL;
				#echo "socket_accept() failed: ".socket_strerror(socket_last_error($this->socket)).PHP_EOL;
				break;
			} else {
				echo "New connection has been accepted.".PHP_EOL;
				$this->onConnect($msgsock);
			}
		} while(TRUE);
	}

	public function onEnd(Process $process) {
		$id = $process->getRunner()->getId();
		echo "Thread for client ".$id." closed.".PHP_EOL;
		fclose($this->clients[$id]);
		Signal::get()->clearHandler($process);
		Signal::get()->clearHandler($process->getRunner()->getQueue());
		unset($this->clients[$id]);
	}

	public function onStart(Process $process) {
		if($process->getRunner() instanceof RunnerServer) {
			$id = $process->getRunner()->getId();
			echo "Thread for client ".$id." spawned.".PHP_EOL;
		}
	}
}
