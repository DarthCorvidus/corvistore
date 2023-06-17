<?php
namespace Net;
class Protocol {
	const OK = 1;
	const COMMAND = 2;
	const SERIAL_PHP = 3;
	const RAW = 4;
	const MESSAGE = 5;
	const ERROR = 254;
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

	function sendMessage(string $message) {
		socket_write($this->socket, \IntVal::uint8()->putValue(self::MESSAGE));
		socket_write($this->socket, \IntVal::uint16LE()->putValue(strlen($message)));
		socket_write($this->socket, $message);
	}
	
	function sendError(string $error) {
		socket_write($this->socket, \IntVal::uint8()->putValue(self::ERROR));
		socket_write($this->socket, \IntVal::uint8()->putValue(strlen($error)));
		socket_write($this->socket, $error);
	}

	function sendOK() {
		socket_write($this->socket, \IntVal::uint8()->putValue(self::OK));
	}
	
	function sendRaw($size, $handle) {
		socket_write($this->socket, \IntVal::uint8()->putValue(self::RAW));
		socket_write($this->socket, \IntVal::uint64LE()->putValue($size));
		$rest = $size;
		while($rest>4096) {
			socket_write($this->socket, fread($handle, 4096));
			$rest -= 4096;
		}
		if($rest!=0) {
			socket_write($this->socket, fread($handle, $rest));
		}
		#$this->sendOK();
	}
	
	private function assertType(int $expected, int $received) {
		if($expected!=$received) {
			throw new \Exception("Invalid server answer, expected ".$expected.", got ".$received);
		}
	}
	
	function getRaw($handle) {
		$init = \IntVal::uint8()->getValue(socket_read($this->socket, 1));
		$this->assertType(self::RAW, $init);
		$size = \IntVal::uint64LE()->getValue(socket_read($this->socket, 8));
		$rest = $size;
		while($rest>4096) {
			fwrite($handle, socket_read($this->socket, 4096));
			$rest -= 4096;
		}
		if($rest!=0) {
			fwrite($handle, socket_read($this->socket, $rest));
		}
	}
	
	function getMessage(): string {
		$init = \IntVal::uint8()->getValue(socket_read($this->socket, 1));
		if($init==self::ERROR) {
			$length = \IntVal::uint8()->getValue(socket_read($this->socket, 1));
			$error = socket_read($this->socket, $length);
			throw new \Exception("Server error: ".$error);
		}
		$this->assertType(self::MESSAGE, $init);
		$length = \IntVal::uint16LE()->getValue(socket_read($this->socket, 2));
		$message = socket_read($this->socket, $length);
	return $message;
	}

	function getOK() {
		$status = \IntVal::uint8()->getValue(socket_read($this->socket, 1));
		if($status!=self::OK) {
			throw new \Exception("expected OK, got ".$status);
		}
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
				$command = socket_read($this->socket, $length);
				if($command=="QUIT") {
					$this->listener->onQuit();
					return;
				}
				$this->listener->onCommand($command, $this);
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