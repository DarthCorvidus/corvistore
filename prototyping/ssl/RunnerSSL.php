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
class RunnerSSL implements Runner  {
	private $clientCount = 0;
	private $sslProtocol = array();
	private $sslClients = array();
	private $ipcClients = array();
	function __construct() {
		echo "Forking off SSL server.".PHP_EOL;
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
		var_dump($this->socket);
	}
	
	public function run() {
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

}
