#!/usr/bin/php
<?php
include __DIR__."/../../vendor/autoload.php";
include __DIR__."/RunnerServer.php";
class Server implements ProcessListener, MessageListener, SignalHandler {
	private $socket;
	private $clients = array();
	private $queue;
	function __construct() {
		set_time_limit(0);
		ob_implicit_flush();
		pcntl_async_signals(true);
		$signal = Signal::get();
		$signal->addSignalHandler(SIGINT, $this);
		$signal->addSignalHandler(SIGTERM, $this);
		$this->queue = new SysVQueue(ftok(__DIR__, "a"));
		$this->queue->addListener($signal, $this);
		$address = '127.0.0.1';
		$port = 4096;
		$this->socket = stream_socket_server("tcp://0.0.0.0:4096", $errno, $errstr);
		stream_set_blocking($this->socket, FALSE);
		if (!$this->socket) {
			throw new Exception($errstr);
		}
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
		$clients = array();

		do {
			pcntl_signal_dispatch();
			#Any activity on the main socket will spawn a new process.
			$read[] = $this->socket;
			$write = NULL;
			$except = NULL;
			/**
			 * This is a bit sucky. The SIGCHLD sent by Process breaks
			 * stream_select early, which is keen on telling everyone.
			 * So either silence it or use pcntl_sigprocmask to shield it,
			 * which results in a short pause when the client disconnects.
			 */
			//pcntl_sigprocmask(SIG_BLOCK, array(SIGCHLD));
			if(@stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				//pcntl_sigprocmask(SIG_UNBLOCK, array(SIGCHLD));
				#$error = socket_last_error($this->socket);
				#if($error!==0) {
				#	echo sprintf("socket_select() failed: %d %s", $error, socket_strerror($error)).PHP_EOL;
				#}
				continue;
			}
			//pcntl_sigprocmask(SIG_UNBLOCK, array(SIGCHLD));
			echo "A new connection has occurred.".PHP_EOL;
			if (($msgsock = stream_socket_accept($this->socket)) === false) {
				echo "socket_accept() failed: ".socket_strerror(socket_last_error($this->socket)).PHP_EOL;
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


$server = new Server();
$server->run();