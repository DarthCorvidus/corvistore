<?php
namespace Server;
use plibv4\process\Task;
use plibv4\process\Scheduler;
use Net\ProtocolAsync;
use Net\AsyncStream;
class TaskServer implements Task {
	private mixed $socket;
	function __construct() {
		$context = new \Net\SSLContext();
		echo \Shared::getSSLAuthorityFile();
		$context->setCAFile(\Shared::getSSLAuthorityFile());
		$context->setPrivateKeyFile(\Shared::getSSLServerKey());
		$context->setCertificateFile(\Shared::getSSLServerCertificate());
		$this->socket = stream_socket_server("ssl://0.0.0.0:4096", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context->getContextServer());
		#$this->socket = stream_socket_server("tcp://0.0.0.0:4096", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
	}
	
	public function __tsError(Scheduler $sched, \Exception $e, int $step): void {
		
	}

	public function __tsFinish(Scheduler $sched): void {
		
	}

	public function __tsKill(Scheduler $sched): void {
		
	}

	public function __tsLoop(Scheduler $sched): bool {
		$read = array();
		$write = array();
		$read[] = $this->socket;
		if(@stream_select($read, $write, $except, $tv_sec = 0) < 1) {
			return true;
		}
		echo "Connection to Server.".PHP_EOL;
		$clientSocket = stream_socket_accept($this->socket);
		$clientTask = new AsyncStream($clientSocket);
		$clientTask->setProtocol(new ProtocolAsync(new FacadeProtocolListener($sched, $clientTask)));
		$sched->addTask($clientTask);
	return true;
	}

	public function __tsPause(Scheduler $sched): void {
		
	}

	public function __tsResume(Scheduler $sched): void {
		
	}

	public function __tsStart(Scheduler $sched): void {
		
	}

	public function __tsTerminate(Scheduler $sched): bool {
		echo "Terminating server process.".PHP_EOL;
		return true;
	}
}