<?php
namespace Server;
class RunnerSSL implements \Runner, \Net\HubServerListener {
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

	public function onDetach(string $name, int $id) {
		;
	}
}
