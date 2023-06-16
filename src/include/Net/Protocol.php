<?php
namespace Net;
class Protocol {
	const OK = 1;
	const COMMAND = 2;
	const SERIAL_PHP = 3;
	const RAW = 4;
	const CANCEL = 255;
	private $socket;
	private $listener;
	function __construct($socket) {
		$this->socket = $socket;
	}
	
	function addProtocolListener(\Net\ProtocolListener $listener) {
		$this->listener = $listener;
	}
	
	function sendCommand(string $command) {
		socket_write($this->socket, \IntVal::uint8()->putValue(self::COMMAND));
		socket_write($this->socket, \IntVal::uint16LE()->putValue(strlen($command)));
		socket_write($this->socket, $command);
	}
	
	function listen() {
		do {
			$read[] = $this->socket;
			$write = NULL;
			$except = NULL;
			if(@socket_select($read, $write, $except, $tv_sec = 5) < 1) {
				if(socket_last_error($this->socket)!==0) {
					echo "socket_select() failed: ".socket_strerror(socket_last_error($this->socket)).PHP_EOL;
				}
				continue;
			}
			if(false === ($binInit = socket_read($this->socket, 1, PHP_BINARY_READ))) {
				echo "socket_read() failed: ".socket_strerror(socket_last_error($this->socket)).PHP_EOL;
				return;
			}
			
			$init = \IntVal::uint8()->getValue($binInit);
			echo "Init: ".$init.PHP_EOL;
			if($init == self::COMMAND) {
				$length = \IntVal::uint16LE()->getValue(socket_read($this->socket, 2));
				echo "Command length: ".$length.PHP_EOL;
				$command = socket_read($this->socket, $length);
				echo "Command: ".$command.PHP_EOL;
				if($command=="QUIT") {
					$this->listener->onQuit();
					return;
				}
				continue;
			}
			
			if($init == self::SERIAL_PHP) {
				$length = \IntVal::uint32LE()->getValue(socket_read($this->socket, 4));
				$rest = $length;
				echo "Structured Data Length: ".number_format($length).PHP_EOL;
				$structured = "";
				while($rest>4096) {
					$structured .= socket_read($this->socket, 4096);
					$rest -= 4096;
				}
				$structured .= socket_read($this->socket, $rest);
				continue;
			}
		} while(true);
	}
}