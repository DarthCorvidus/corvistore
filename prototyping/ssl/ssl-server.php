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
		$context = stream_context_create();
		
		
		stream_context_set_option($context, 'ssl', 'local_cert', __DIR__."/server.crt");
		stream_context_set_option($context, 'ssl', 'local_pk', __DIR__."/server.key");
		stream_context_set_option($context, 'ssl', 'local_ca', __DIR__."/ca.crt");
		#stream_context_set_option($context, 'ssl', 'passphrase', "");
		#stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
		stream_context_set_option($context, 'ssl', 'verify_peer', false);
		
		$context = new Net\SSLContext();
		$context->setCAFile(__DIR__."/ca.crt");
		$context->setPrivateKeyFile(__DIR__."/server.key");
		$context->setCertificateFile(__DIR__."/server.crt");
		
		
		$this->socket = stream_socket_server("tcp://0.0.0.0:4096", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context->getContextServer());
		stream_set_blocking($this->socket, TRUE);
		#if (! stream_socket_enable_crypto ($this->socket, true, STREAM_CRYPTO_METHOD_TLS_SERVER )) {
		#	exit();
		#}

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
	
	private function streamSelect(): array {
		$read = array();
		$read["mainServer"] = $this->socket;
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
		//pcntl_sigprocmask(SIG_UNBLOCK, array(SIGCHLD));
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
		$this->onConnect($msgsock);
	}
	
	function run() {
		$clients = array();
		do {
			pcntl_signal_dispatch();
			#Any activity on the main socket will spawn a new process.
			$read = array();
			$read["mainServer"] = $this->socket;
			$write = NULL;
			$except = NULL;
			/**
			 * This is a bit sucky. The SIGCHLD sent by Process breaks
			 * stream_select early, which is keen on telling everyone.
			 * So either silence it or use pcntl_sigprocmask to shield it,
			 * which results in a short pause when the client disconnects.
			 */
			//pcntl_sigprocmask(SIG_BLOCK, array(SIGCHLD));
			#if(@stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				//pcntl_sigprocmask(SIG_UNBLOCK, array(SIGCHLD));
				#$error = socket_last_error($this->socket);
				#if($error!==0) {
				#	echo sprintf("socket_select() failed: %d %s", $error, socket_strerror($error)).PHP_EOL;
				#}
				#continue;
			#}
			$array = $this->streamSelect();
			if(empty($array)) {
				continue;
			}
			if(isset($array["mainServer"])) {
				$this->newServerClient();
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