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
		while(true) {
			$write = array();
			$read = array($this->socket);
			if(@stream_select($read, $write, $except, $tv_sec = 1) < 1) {
				echo "Stream not ready to read.".PHP_EOL;
				continue;
			}
			
			return fread($this->socket, $amount);
		}
	}

	public function write($string) {
		while(true) {
			$write = array($this->socket);
			$read = array();
			if(@stream_select($read, $write, $except, $tv_sec = 1) < 1) {
				echo "Stream not ready to write.".PHP_EOL;
				continue;
			}
			fwrite($this->socket, $string);
		}
	}

}
