<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RunnerSSL
 *
 * @author hm
 */
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
		#stream_context_set_option($context, 'ssl', 'local_cert', __DIR__."/server.crt");
		#stream_context_set_option($context, 'ssl', 'local_pk', __DIR__."/server.key");
		#stream_context_set_option($context, 'ssl', 'local_ca', __DIR__."/ca.crt");
		#stream_context_set_option($context, 'ssl', 'passphrase', "");
		#stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
		#stream_context_set_option($context, 'ssl', 'verify_peer', false);
		
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
	return;
		echo "Looking for connections";
		$this->init();
		do {
			$read = array();
			$read["mainServer"] = $this->socket;
			foreach($this->sslClients as $key => $value) {
				$read[$key] = $value;
			}
			if(@stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				continue;
			}

			#print_r($array);
			if(isset($read["mainServer"])) {
				echo "A new connection has occurred.".PHP_EOL;
				if (($msgsock = stream_socket_accept($this->socket)) === false) {
					echo "socket_accept() failed: ".socket_strerror(socket_last_error($this->socket)).PHP_EOL;
					#stream_set_blocking($msgsock, TRUE);
					return;
				}
				echo "New connection has been accepted.".PHP_EOL;
				#stream_set_blocking($msgsock, true);
				#if (! stream_socket_enable_crypto ($msgsock, true, STREAM_CRYPTO_METHOD_TLS_SERVER )) {
				#	exit();
				#}
				$this->sslClients[$this->clientCount] = $msgsock;
				$this->sslProtocol[$this->clientCount] = new \Net\ProtocolBase($msgsock);
				$this->sslProtocol[$this->clientCount]->sendMessage("Connected as client ".$this->clientCount);
				$this->ipcClients[$this->clientCount] = stream_socket_client("unix://ssl-server.socket", $errno, $errstr, NULL, STREAM_CLIENT_CONNECT);
				fwrite($this->ipcClients[$this->clientCount], IntVal::uint64SE()->putValue($this->clientCount));
				$this->clientCount++;
			}
			$k = 0;
			foreach($read as $key => $value) {
				if($key==="mainServer") {
					continue;
				}
				if(!isset($this->sslClients[$key])) {
					echo "SSL connection for ".$key." went away.".PHP_EOL;
					continue;
				}
				$command = $this->sslProtocol[$key]->getCommand();
				echo sprintf("Client %d sent message: %s", $key, $command).PHP_EOL;
				$this->sslProtocol[$key]->sendMessage("You sent: ".$command);
				fwrite($this->ipcClients[$key], $command.PHP_EOL);
				if($command=="quit") {
					fclose($this->sslClients[$key]);
					unset($this->sslClients[$key]);
					unset($this->sslProtocol[$key]);
				}
				
				if($command=="crash") {
					throw new Exception("I was told to crash.");
				}
			}
		} while(TRUE);
	}

	public function onConnect(string $name, int $id, $newClient) {
		$this->writeBuffer[$name.":".$id] = "";
		$this->sslProtocol[$name.":".$id] = new \Net\ProtocolReactive(new SSLProtocolListener($id));
		$this->sslProtocol[$name.":".$id]->sendMessage("Welcome to Test SSL Server 1.0");
		$this->sslProtocol[$name.":".$id]->sendMessage("(c) ACME Backup Software");
		$this->sslProtocol[$name.":".$id]->sendMessage("Connected on ".date("Y-m-d H:i:s")." as client ".$id);
		#$this->hub->addWriteBuffer($name, $id, str_pad("Welcome to Test SSL Server 1.0", $this->getPacketLength($name, $id)));
		#$this->hub->addWriteBuffer($name, $id, str_pad("(c) ACME Backup Software", $this->getPacketLength($name, $id)));
		#$connected = "Connected on ".date("Y-m-d H:i:s")." as client ".$id;
		#$this->hub->addWriteBuffer($name, $id, str_pad($connected, $this->getPacketLength($name, $id)));
	return $this->sslProtocol[$name.":".$id];
		#$this->sslProtocol[$id] = new \Net\ProtocolBase($newClient);
		#$this->sslProtocol[$id]->sendMessage("Connected as client ".$id);
		#$ipcClient = stream_socket_client("unix://ssl-server.socket", $errno, $errstr, NULL, STREAM_CLIENT_CONNECT);
		#$this->hub->addCustomStream("ipc", $id, $ipcClient);
	}
	
	public function hasClientListener(string $name, int $id): bool {
		return true;
	}
	
	public function getClientListener(string $name, int $id): \Net\HubClientListener {
		return $this->sslProtocol[$name.":".$id];
	}

	public function onRead(string $name, int $id, string $data) {
		if($name == "ssl") {
			echo "Receive ".trim($data).PHP_EOL;
			if(trim($data) == "status") {
				$this->writeBuffer[$name.":".$id] = str_pad("Status: connection #".$id, $this->getPacketLength($name, $id));
			return;
			}
			
			if(trim($data) == "halt") {
				echo "Halting SSL server on client ".$id." request.".PHP_EOL;
				exit();
			}
			$this->writeBuffer[$name.":".$id] = str_pad("Unknown command '".trim($data)."'", $this->getPacketLength($name, $id));
		}
		#if($name == "ssl") {
		#	$forward = fread($stream, 1024);
		#	fwrite($this->hub->getStream("ipc", $id), $forward);
		#return;
		#}
		#if($name == "ipc") {
		#	$forward = fread($stream, 1024);
		#	fwrite($this->hub->getStream("ssl", $id), $forward);
		#}
	}

	public function onWrite(string $name, int $id): string {
		$value = $this->writeBuffer[$name.":".$id];
		$this->writeBuffer[$name.":".$id] = "";
	return $value;
	}

	public function getBinary(string $name, int $id): bool {
		if($name=="ssl") {
			return TRUE;
		}
	}

	public function getPacketLength(string $name, int $id): int {
		return 1024;
	}

	public function hasWrite(string $name, int $id): bool {
		if($this->writeBuffer[$name.":".$id]!=="") {
			return TRUE;
		}
		return false;
	}

	public function onDisconnect() {
		echo "Client disconnected".PHP_EOL;
		#echo "Client ".$id." disconnected.".PHP_EOL;
		#unset($this->writeBuffer[$name.":".$id]);
	}

	public function onCommand(string $command) {
		echo $command.PHP_EOL;
		/*
		 * Common issue of this whole callback/listener scheme: We have
		 * absolutely no clue who we are. 
		 */
		if($command=="quit") {
			echo "Client disconnected.".PHP_EOL;
		}
	}

	public function onMessage(string $message) {
		
	}

}
