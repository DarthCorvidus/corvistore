<?php
class StreamHub {
	private $server = array();
	private $clients = array();
	private $streamHubListeners;
	private $clientListeners;
	private $counters = array();
	function __construct() {
		;
	}
	
	function detach($name, $id) {
		unset($this->clients[$name.":".$id]);
	}
	
	function addServer(string $name, $stream) {
		$this->server[$name] = $stream;
		$this->counters[$name] = 0;
	}
	
	function addClientStream(string $name, int $id, $stream, \Net\HubClientListener $listener) {
		$this->clients[$name.":".$id] = $stream;
		$this->clientListeners[$name.":".$id] = $listener;
	}
	
	function getStream(string $name, int $id) {
		return $this->clients[$name.":".$id];
	}
	
	function addStreamHubListener(string $name, StreamHubListener $listener) {
		$this->streamHubListeners[$name] = $listener;
	}
	
	function close(string $name, int $id) {
		fclose($this->clients[$name.":".$id]);
		$this->detach($name, $id);
	}
	
	private function read(string $key, \Net\HubClientListener $listener) {
		$exp = explode(":", $key);
		$name = $exp[0];
		$id = (int)$exp[1];
		if(!$listener->getBinary($name, $id)) {
			$data = trim(fgets($this->clients[$key]));
			$listener->onRead($name, $id, $data);
		}
	}
	
	function listen() {
		while(TRUE) {
			$read = array();
			$write = array();
			foreach($this->clients as $key => $value) {
				$read[$key] = $value;
			}
			foreach($this->server as $key => $value) {
				$read[$key] = $value;
			}
			if(@stream_select($read, $write, $except, $tv_sec = 5) < 1) {
				#$error = socket_last_error($this->socket);
				#if($error!==0) {
				#	echo sprintf("socket_select() failed: %d %s", $error, socket_strerror($error)).PHP_EOL;
				#}
				continue;
			}
			foreach($read as $key => $value) {
				if(in_array($key, array_keys($this->server), TRUE)) {
					$client = stream_socket_accept($value);
					$next = $this->counters[$key];
					$this->counters[$key]++;
					$this->clients[$key.":".$next] = $client;
					$this->streamHubListeners[$key]->onConnect($key, $next, $client);
					continue;
				}
				$this->read($key, $this->clientListeners[$key]);
				
				#$this->streamHubListeners[$exp[0]]->onRead($exp[0], (int)$exp[1], $value);
			}
		}
	}
}
