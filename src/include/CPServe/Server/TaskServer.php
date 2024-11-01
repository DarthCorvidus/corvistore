<?php
namespace Server;
class TaskServer implements \plibv4\process\Task {
	private \plibv4\process\Timeshare $ts;
	private $socket;
	function __construct(\plibv4\process\Timeshare $ts) {
		$this->ts = $ts;
		$context = new \Net\SSLContext();
		echo \Shared::getSSLAuthorityFile();
		$context->setCAFile(\Shared::getSSLAuthorityFile());
		$context->setPrivateKeyFile(\Shared::getSSLServerKey());
		$context->setCertificateFile(\Shared::getSSLServerCertificate());
		$this->socket = stream_socket_server("ssl://0.0.0.0:4096", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context->getContextServer());
		#$this->socket = stream_socket_server("tcp://0.0.0.0:4096", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
	}
	
	public function __tsError(\Exception $e, int $step): void {
		
	}

	public function __tsFinish(): void {
		
	}

	public function __tsKill(): void {
		
	}

	public function __tsLoop(): bool {
		$read = array();
		$write = array();
		$read[] = $this->socket;
		if(@stream_select($read, $write, $except, $tv_sec = 0) < 1) {
			return true;
		}
		echo "Connection to Server.".PHP_EOL;
		$clientSocket = stream_socket_accept($this->socket);
		$clientTask = new TaskClient($this->ts, $clientSocket);
		$this->ts->addTask($clientTask);
	return true;
	}

	public function __tsPause(): void {
		
	}

	public function __tsResume(): void {
		
	}

	public function __tsStart(): void {
		
	}

	public function __tsTerminate(): bool {
		echo "Terminating server process.".PHP_EOL;
		return true;
	}
}