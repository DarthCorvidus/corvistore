<?php
namespace Server;
class RunnerSSL implements \Runner, \Net\HubServerListener, \Net\HubClientNamedListener {
	private $clientCount = 0;
	private $sslProtocol = array();
	private $sslClients = array();
	private $ipcClients = array();
	private $hub;
	private $socket;
	private $writeBuffer;
	function __construct() {
		echo "Forking off SSL server.".PHP_EOL;
		$this->hub = new \StreamHub();
	}

	private function init() {
		echo "Initialize SSL".PHP_EOL;
		$context = new \Net\SSLContext();
		echo \Shared::getSSLAuthorityFile();
		$context->setCAFile(\Shared::getSSLAuthorityFile());
		$context->setPrivateKeyFile(\Shared::getSSLServerKey());
		$context->setCertificateFile(\Shared::getSSLServerCertificate());
		$this->socket = stream_socket_server("ssl://0.0.0.0:4096", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context->getContextServer());
		$this->hub->addServer("ssl", $this->socket, $this);
		#$this->hub->addStreamHubListener("ssl", $this);
		#$this->hub->addStreamHubListener("ipc", $this);
	}
	
	public function run() {
		$this->init();
		$this->hub->listen();
	}

	public function onConnect(string $name, int $id, $newClient) {
		$this->writeBuffer[$name.":".$id] = array();
		$this->writeBuffer["ipc:".$id] = array();
		#$this->sslProtocol[$name.":".$id] = new \Net\ProtocolReactive(new SSLProtocolListener($id));
		#$this->sslProtocol[$name.":".$id]->sendMessage("Welcome to Test SSL Server 1.0");
		#$this->sslProtocol[$name.":".$id]->sendMessage("(c) ACME Backup Software");
		#$this->sslProtocol[$name.":".$id]->sendMessage("Connected on ".date("Y-m-d H:i:s")." as client ".$id);
		$ipcClient = stream_socket_client("unix://".\Shared::getIPCSocket(), $errno, $errstr, NULL, STREAM_CLIENT_CONNECT);
		$this->hub->addClientStream("ipc", $id, $ipcClient);
		$this->hub->addForward("ssl", $id, "ipc", $id, 1024);
		$this->hub->addForward("ipc", $id, "ssl", $id, 1024);
	}
	
	public function hasClientListener(string $name, int $id): bool {
		return false;
	}
	
	public function getClientListener(string $name, int $id): \Net\HubClientListener {
		
	}
	
	public function hasClientNamedListener(string $name, int $id): bool {
		return false;
	}
	
	public function getClientNamedListener(string $name, int $id): \Net\HubClientNamedListener {
		return $this;
	}

	public function getBinary(string $name, int $id): bool {
		return TRUE;
	}

	public function getPacketLength(string $name, int $id): int {
		return 1024;
	}

	public function hasWrite(string $name, int $id): bool {
		return !empty($this->writeBuffer[$name.":".$id]);
	}

	public function onDisconnect(string $name, int $id) {
		if($name=="ssl") {
			$this->hub->close("ipc", $id);
		}
	}

	public function onDetach(string $name, int $id) {
		;
	}
	
	public function onRead(string $name, int $id, string $data) {
		if($name=="ssl") {
			$this->writeBuffer["ipc:".$id][] = $data;
		} else {
			$this->writeBuffer["ssl:".$id][] = $data;
		}
	}

	public function onWrite(string $name, int $id): string {
		return array_shift($this->writeBuffer[$name.":".$id]);
	}

	public function onWritten(string $name, int $id) {
		
	}

}
