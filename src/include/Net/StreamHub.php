<?php
class StreamHub {
	private $server;
	private $clients;
	private $streamHubListeners;
	function __construct() {
		;
	}
	
	function addServer(string $name, $stream) {
		$this->server[$name] = $stream;
	}
	
	function addCustomStream(string $name, $stream) {
		$this->clients[$name.":0"] = $stream;
	}
	
	function addStreamHubListener(string $name, StreamHubListener $listener) {
		$this->streamHubListeners[$name] = $listener;
	}
	
	function close(string $name, int $id) {
		fclose($this->clients[$name.":".$id]);
	}
	
	function listen() {
		while(TRUE) {
			$read = array();
			$write = array();
			foreach($this->clients as $key => $value) {
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
				$exp = explode(":", $key);
				$this->streamHubListeners[$exp[0]]->onRead($exp[0], (int)$exp[1], $value);
			}
		}
	}
}
