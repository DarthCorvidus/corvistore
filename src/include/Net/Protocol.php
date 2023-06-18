<?php
namespace Net;
class Protocol {
	const OK = 1;
	const COMMAND = 2;
	const SERIAL_PHP = 3;
	const RAW = 4;
	const MESSAGE = 5;
	const FILE = 6;
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
		$this->sendOK();
		$i = 0;
		while($rest>4096) {
			socket_write($this->socket, fread($handle, 4096));
			$rest -= 4096;
			$i++;
			if($i%10==0) {
				$this->sendOK();
			}
		}
		if($rest!=0) {
			socket_write($this->socket, fread($handle, $rest));
		}
		$this->sendOK();
	}

	function sendSerializePHP($unserialized) {
		$serialized = serialize($unserialized);
		$size = strlen($serialized);
		socket_write($this->socket, \IntVal::uint8()->putValue(self::SERIAL_PHP));
		socket_write($this->socket, \IntVal::uint32LE()->putValue($size));
		$rest = $size;
		$pos = 0;
		while($rest>4096) {
			socket_write($this->socket, substr($serialized, $pos, 4096));
			$rest -= 4096;
			$pos += 4096;
		}
		if($rest!=0) {
			socket_write($this->socket, substr($serialized, $pos));
		}
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
		$i=0;
		$this->getOK();
		while($rest>4096) {
			$read = "";
			socket_recv($this->socket, $read, 4096, MSG_WAITALL);
			fwrite($handle, $read);
			$rest -= 4096;
			$i++;

			if($i%10==0) {
				$this->getOK();
			}
		}
		if($rest!=0) {
			fwrite($handle, socket_read($this->socket, $rest));
		}
		$this->getOK();
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

	function getUnserializePHP() {
		$init = \IntVal::uint8()->getValue(socket_read($this->socket, 1));
		$this->assertType(self::SERIAL_PHP, $init);
		$size = \IntVal::uint32LE()->getValue(socket_read($this->socket, 4));
		
		$serialized = "";
		$rest = $size;
		$pos = 0;
		while($rest>4096) {
			$serialized .= socket_read($this->socket, 4096);
			$rest -= 4096;
		}
		if($rest!=0) {
			$serialized .= socket_read($this->socket, $rest);
		}
	return unserialize($serialized);
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
		} while(true);
	}
}