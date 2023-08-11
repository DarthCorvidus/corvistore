<?php
class StreamHub {
	private $server = array();
	private $clients = array();
	private $streamHubListeners = array();
	private $clientListeners;
	private $counters = array();
	private $serverListeners = array();
	private $zeroCounter = array();
	private $clientBuffers = array();
	function __construct() {
		;
	}
	
	private function hasClientListener(string $key) {
		return !empty($this->clientListeners[$key]);
	}
	private function getClientListener(string $key): Net\HubClientListener {
		return $this->clientListeners[$key];
	}
	
	function detach($name, $id) {
		unset($this->clients[$name.":".$id]);
	}
	
	function addServer(string $name, $stream, \Net\HubServerListener $listener) {
		$this->server[$name] = $stream;
		$this->counters[$name] = 0;
		$this->serverListeners[$name] = $listener;
	}
	
	function addClientStream(string $name, int $id, $stream, \Net\HubClientListener $listener) {
		$this->clients[$name.":".$id] = $stream;
		$this->clientListeners[$name.":".$id] = $listener;
		$this->zeroCounter[$name.":".$id] = 0;
		$this->clientBuffers[$name.":".$id] = array();
	}
	
	function getStream(string $name, int $id) {
		return $this->clients[$name.":".$id];
	}
	
	function addWriteBuffer(string $name, int $id, string $data) {
		$this->clientBuffers[$name.":".$id][] = $data;
	}
	
	#function addStreamHubListener(string $name, StreamHubListener $listener) {
	#	$this->streamHubListeners[$name] = $listener;
	#}
	
	function close(string $name, int $id) {
		fclose($this->clients[$name.":".$id]);
		unset($this->clients[$name.":".$id]);
		$this->getClientListener($name.":".$id)->onDisconnect($name, $id);
		unset($this->clientListeners[$name.":".$id]);
		$this->detach($name, $id);
	}
	
	private function read(string $key) {
		$exp = explode(":", $key);
		$name = $exp[0];
		$id = (int)$exp[1];
		$listener = $this->getClientListener($key);
		if(!$listener->getBinary($name, $id)) {
			$data = trim(fgets($this->clients[$key]));
			$listener->onRead($name, $id, $data);
		}
		if($listener->getBinary($name, $id)) {
			$this->readBinary($key);
		}
	}

	private function readBinary(string $key) {
		$exp = explode(":", $key);
		$name = $exp[0];
		$id = (int)$exp[1];
		$listener = $this->getClientListener($key);
		$data = fread($this->clients[$key], $listener->getPacketLength($name, $id));
		$len = strlen($data);
		
		/*
		 * If a connection goes away, stream_select will trigger read() in a
		 * very short succession. I don't know whether this check is feasible,
		 * but it is a start: if the counter gets increased to 1000, end this
		 * client.
		 */
		if($len===0 && $this->zeroCounter[$key]<1000) {
			$this->zeroCounter[$key]++;	
			return;
		}
		if($len===0 && $this->zeroCounter[$key]==1000) {
			$this->close($name, $id);
			return;
		}
		/*
		 * I cannot really be sure that I got all bytes as mandated by
		 * HubClientListener::getPacketLength, but for now, this is sufficient.
		 */
		$listener->onRead($name, $id, $data);
	}
	
	private function write(string $key) {
		$exp = explode(":", $key);
		$name = $exp[0];
		$id = (int)$exp[1];
		$listener = $this->getClientListener($key);
		if(!$listener->hasWrite($name, $id) and empty($this->clientBuffers[$key])) {
			return;
		}
		$this->clientBuffers[$key][] = $listener->onWrite($name, $id);
		if(!$listener->getBinary($name, $id)) {
			$write = array_shift($this->clientBuffers[$key]);
			fwrite($this->clients[$key], $listener->onWrite($name, $id).PHP_EOL);
		}
		if($listener->getBinary($name, $id)) {
			$write = array_shift($this->clientBuffers[$key]);
			fwrite($this->clients[$key], $write);
		}
	}
	
	function listen() {
		while(TRUE) {
			$read = array();
			$write = array();
			foreach($this->clients as $key => $value) {
				$read[$key] = $value;
			}
		
			foreach($this->clients as $key => $value) {
				$write[$key] = $value;
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
					$this->serverListeners[$key]->onConnect($key, $next, $client);
					if($this->serverListeners[$key]->hasClientListener($key, $next)) {
						$listener = $this->serverListeners[$key]->getClientListener($key, $next);
						$this->clientListeners[$key.":".$next] = $listener;
					}
					$this->zeroCounter[$key.":".$next] = 0;
					#$this->streamHubListeners[$key]->onConnect($key, $next, $client);
					continue;
				}
				$this->read($key);
				
				#$this->streamHubListeners[$exp[0]]->onRead($exp[0], (int)$exp[1], $value);
			}
			
			foreach($write as $key => $value) {
				if(in_array($key, array_keys($this->server), TRUE)) {
					continue;
				}
				/*
				 * this is necessary, because the client listener could have
				 * gone away as part of some read action.
				 */
				if(!isset($this->clientListeners[$key])) {
					continue;
				}
				$this->write($key, $this->clientListeners[$key]);
			}
		}
	}
}
