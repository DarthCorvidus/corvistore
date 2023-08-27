<?php
namespace Net;
class StreamClient implements Stream {
	private $socket;
	function __construct($socket) {
		$this->socket = $socket;
	}
	public function close() {
		fclose($this->socket);
	}

	public function read(int $amount): string {
		return fread($this->socket, $amount);
	}

	public function write($string) {
		fwrite($this->socket, $string);
	}

}
