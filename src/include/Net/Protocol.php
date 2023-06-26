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
	
	private function read(int $amount): string {
		$read = "";
		#\plibv4\profiler\Profiler::startTimer("read");
		socket_recv($this->socket, $read, $amount, MSG_WAITALL);
		#\plibv4\profiler\Profiler::endTimer("read");
	return $read;
	}

	private function write(string $data) {
		#\plibv4\profiler\Profiler::startTimer("write");
		socket_write($this->socket, $data);
		#\plibv4\profiler\Profiler::endTimer("write");
	}
	
	function addProtocolListener(\Net\ProtocolListener $listener) {
		$this->listener = $listener;
	}
	
	function sendCommand(string $command) {
		$data = \IntVal::uint8()->putValue(self::COMMAND);
		$data .= \IntVal::uint16LE()->putValue(strlen($command));
		$data .= $command;
		$this->write($data);
	}

	function sendMessage(string $message) {
		$this->write(\IntVal::uint8()->putValue(self::MESSAGE));
		$this->write(\IntVal::uint16LE()->putValue(strlen($message)));
		$this->write($message);
	}
	
	function sendError(string $error) {
		$this->write(\IntVal::uint8()->putValue(self::ERROR));
		$this->write(\IntVal::uint8()->putValue(strlen($error)));
		$this->write($error);
	}

	function sendOK() {
		$this->write(\IntVal::uint8()->putValue(self::OK));
	}

	function sendCancel() {
		$this->write(\IntVal::uint8()->putValue(self::CANCEL));
	}
	
	function sendRaw($size, $handle) {
		$this->write(\IntVal::uint8()->putValue(self::RAW));
		$this->write(\IntVal::uint64LE()->putValue($size));
		$rest = $size;
		$this->sendOK();
		$i = 0;
		while($rest>4096) {
			$this->write(fread($handle, 4096));
			$rest -= 4096;
			$i++;
			if($i%10==0) {
				$this->sendOK();
			}
		}
		if($rest!=0) {
			$this->write(fread($handle, $rest));
		}
		$this->sendOK();
	}
	private function checkFile(\File $file) {
		clearstatcache();
		$path = $file->getPath();
		if(!is_file($path)) {
			throw new UploadException("file has vanished during upload.");
		}

		if(filesize($path)!=$file->getSize()) {
			throw new UploadException("filesize has changed during upload.");
		}

		if(filemtime($path)!=$file->getMTime()) {
			throw new UploadException("file has changed during upload.");
		}
	}
	
	function sendFile(\File $file) {
		$file->reload();
		$this->checkFile($file);
		$this->write(\IntVal::uint8()->putValue(self::RAW));
		$this->write(\IntVal::uint64LE()->putValue($file->getSize()));
		$rest = $file->getSize();
		$this->sendOK();
		$i = 0;
		$handle = fopen($file->getPath(), "r");
		while($rest>4096) {
			$this->write(fread($handle, 4096));
			$rest -= 4096;
			$i++;
			if($i%10==0) {
				try {
					$this->checkFile($file);
					$this->sendOK();
				} catch(UploadException $e) {
					$this->sendCancel();
					fclose($handle);
					throw $e;
				}
				
			}
		}
		if($rest!=0) {
			$this->write(fread($handle, $rest));
		}
		try {
			$this->checkFile($file);
			$this->sendOK();
		} catch(UploadException $e) {
			$this->sendCancel();
			fclose($handle);
			throw $e;
		}
		fclose($handle);
	}

	function sendSerializePHP($unserialized) {
		$serialized = serialize($unserialized);
		$size = strlen($serialized);
		$header = \IntVal::uint8()->putValue(self::SERIAL_PHP);
		$header .= \IntVal::uint32LE()->putValue($size);
		/*
		 * We prepend the header to the payload; it will be sent with the first
		 * chunk and read by one and by four bytes, before the payload is read.
		 */
		$serialized = $header.$serialized;
		$rest = $size;
		$pos = 0;
		while($rest>4096) {
			$this->write(substr($serialized, $pos, 4096));
			$rest -= 4096;
			$pos += 4096;
		}
		if($rest!=0) {
			$this->write(substr($serialized, $pos));
		}
	}
	
	private function assertType(int $expected, int $received) {
		if($expected!=$received) {
			throw new \Exception("Invalid server answer, expected ".$expected.", got ".$received);
		}
	}
	
	function getRaw(TransferListener $listener) {
		$init = \IntVal::uint8()->getValue($this->read(1));
		$this->assertType(self::RAW, $init);
		$size = \IntVal::uint64LE()->getValue($this->read(8));
		$rest = $size;
		$i=0;
		try {
			$this->getOK();
			$listener->onStart($size);
			while($rest>4096) {
				$listener->onData($this->read(4096));
				$rest -= 4096;
				$i++;

				if($i%10==0) {
					$this->getOK();
				}
			}
			if($rest!=0) {
				$listener->onData($this->read($rest));
			}
			$this->getOK();
			$listener->onEnd();
		} catch (\Net\CancelException $e) {
			$listener->onCancel();
		}
	}
	
	function getMessage(): string {
		$init = \IntVal::uint8()->getValue($this->read(1));
		if($init==self::ERROR) {
			$length = \IntVal::uint8()->getValue($this->read(1));
			$error = $this->read($length);
			throw new \Exception("Server error: ".$error);
		}
		$this->assertType(self::MESSAGE, $init);
		$length = \IntVal::uint16LE()->getValue($this->read(2));
		$message = $this->read($length);
	return $message;
	}

	function getUnserializePHP() {
		$init = \IntVal::uint8()->getValue($this->read(1));
		$this->assertType(self::SERIAL_PHP, $init);
		$size = \IntVal::uint32LE()->getValue($this->read(4));
		
		$serialized = "";
		$rest = $size;
		$pos = 0;
		while($rest>4096) {
			$serialized .= $this->read(4096);
			$rest -= 4096;
		}
		if($rest!=0) {
			$serialized .= $this->read($rest);
		}
	return unserialize($serialized);
	}

	function getOK() {
		$status = \IntVal::uint8()->getValue($this->read(1));
		if($status==self::CANCEL) {
			throw new \Net\CancelException("expected OK, got CANCEL");
		}
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
			$init = \IntVal::uint8()->getValue($this->read(1));
			if($init == self::COMMAND) {
				$length = \IntVal::uint16LE()->getValue($this->read(2));
				$command = $this->read($length);
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