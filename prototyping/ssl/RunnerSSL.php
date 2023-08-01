<?php
class RunnerSSL implements Runner, \Net\HubServerListener {
	private $clientCount = 0;
	private $sslProtocol = array();
	private $sslClients = array();
	private $ipcClients = array();
	private $hub;
	private $socket;
	private $writeBuffer;
	function __construct() {
		echo "Forking off SSL server.".PHP_EOL;
		$this->hub = new StreamHub();
	}

	private function init() {
		echo "Initialize SSL".PHP_EOL;
		$context = new Net\SSLContext();
		$context->setCAFile(__DIR__."/ca.crt");
		$context->setPrivateKeyFile(__DIR__."/server.key");
		$context->setCertificateFile(__DIR__."/server.crt");
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
		$this->writeBuffer[$name.":".$id] = "";
		$this->sslProtocol[$name.":".$id] = new \Net\ProtocolReactive(new SSLProtocolListener($id));
		$this->sslProtocol[$name.":".$id]->sendMessage("Welcome to Test SSL Server 1.0");
		$this->sslProtocol[$name.":".$id]->sendMessage("(c) ACME Backup Software");
		$this->sslProtocol[$name.":".$id]->sendMessage("Connected on ".date("Y-m-d H:i:s")." as client ".$id);
		#$ipcClient = stream_socket_client("unix://ssl-server.socket", $errno, $errstr, NULL, STREAM_CLIENT_CONNECT);
		#$this->hub->addCustomStream("ipc", $id, $ipcClient);
	}
	
	public function hasClientListener(string $name, int $id): bool {
		return true;
	}
	
	public function getClientListener(string $name, int $id): \Net\HubClientListener {
		return $this->sslProtocol[$name.":".$id];
	}
}
