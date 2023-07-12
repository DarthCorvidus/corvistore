<?php
namespace Net;
class ProtocolBase {
	private $socket;
	private $readLength = 16;
	private $writeLength = 16;
	const OK = 1;
	const MESSAGE = 2;
	const FILE = 3;
	const COMMAND = 3;
	const SERIALIZE_PHP = 4;
	// File changed during transfer
	const ERROR_CHANGED = 128;
	// Unable to write
	const ERROR_UTW = 129;
	// Unable to read
	const ERROR_UTR = 130;
	// general error
	const ERROR = 255;
	function __construct($socket, $readLength = 1024, $writeLength = 1024) {
		$this->socket = $socket;
		$this->readLength = $readLength;
		$this->writeLength = $writeLength;
	}

	static function padRandom(string $string, $padlength): string {
		$len = strlen($string);
		if($len==$padlength) {
			return $string;
		}
		if($len<$padlength) {
			return $string.random_bytes($padlength-$len);
		}
		/*
		 * Throw exception here, as a longer pad length should not happen in the
		 * context of ProtocolBase.
		 */
		if($len>$padlength) {
			throw new \RuntimeException("padlength ".$padlength." shorter than strlen ".$len);
		}
	}
	
	private function read(): string {
		$read = fread($this->socket, $this->readLength);
	return $read;
	}
	
	private function write(string $string) {
		fwrite($this->socket, $string);
	}
	
	function sendString(int $type, string $string) {
		$trlen = strlen($string);
		$type = \IntVal::uint8()->putValue($type);
		$length = \IntVal::uint32LE()->putValue($trlen);
		$send = $type.$length.$string;
		$rest = strlen($send);
		$array = array();
		$i=0;
		while($rest>$this->writeLength) {
			$this->write(substr($send, $i*$this->writeLength, $this->writeLength));
			$rest -= $this->writeLength;
			$i++;
		}
		if($rest>0) {
			$random = "";
			$pad = $this->writeLength-$rest;
			if($pad>0) {
				$random = random_bytes($pad);
			}
			$this->write(substr($send, $i*$this->writeLength, $this->readLength).$random);
		}
	}
	
	function getString(int $type): string {
		$first = fread($this->socket, $this->readLength);
		$readType = \IntVal::uint8()->getValue($first[0]);
		/*
		 *  ProtocolMismatch is thrown if the type sent is not the same as the
		 *  type expected. ERROR will be handled differently.
		 */
		
		if($type != $readType && $readType != self::ERROR) {
			throw new ProtocolMismatchException("Protocol mismatch: expected ".$type.", got ".$readType);
		}
		
		$length = \IntVal::uint32LE()->getValue($first[1].$first[2].$first[3].$first[4]);
		$result = "";
		/*
		 * If the whole message fits into the first block (minus header length),
		 * we can quit early.
		 */
		if($length<$this->readLength-5) {
			$result = substr($first, 5, $length);
			if($readType==self::ERROR) {
				throw new ProtocolErrorException($result);
			}
			return substr($first, 5, $length);
		}
		$result .= substr($first, 5, $length);
		$rest = ($length+5)-$this->readLength;
		while($rest>$this->readLength) {
			$result .= $this->read($this->readLength);
			$rest -= $this->readLength;
		}
		if($rest>0) {
			$result .= substr($this->read($this->readLength), 0, $rest);
		}
		if($readType==self::ERROR) {
			throw new ProtocolErrorException($result);
		}
	return $result;
	}

	function sendCommand(string $command) {
		$this->sendString(self::COMMAND, $command);
	}

	function getCommand(): string {
		return $this->getString(self::COMMAND);
	}

	function sendMessage(string $message) {
		$this->sendString(self::MESSAGE, $message);
	}

	function getMessage(): string {
		return $this->getString(self::MESSAGE);
	}

	function sendError(string $error) {
		$this->sendString(self::ERROR, $error);
	}
	
	function sendSerializePHP($serialize) {
		$serialized = serialize($serialize);
		$this->sendString(self::SERIALIZE_PHP, $serialized);
	}
	
	function getSerializedPHP() {
		$unserialized = unserialize($this->getString(self::SERIALIZE_PHP));
	return $unserialized;
	}

	function sendStream(StreamSender $sender) {
			$size = $sender->getSendSize();
			$sender->onSendStart();
			$header = \IntVal::uint8()->putValue(self::FILE);
			$header .= \IntVal::uint64LE()->putValue($size);

			//Data which is shorter & equal the write length can be sent at once.
			if($size+strlen($header)<=$this->writeLength) {
				$send = $header;
				$send .= $sender->getSendData($size);
				$this->write(self::padRandom($send, $this->writeLength));
				$sender->onSendEnd();
			return;
			}
		$i = 0;
		while($sender->getSendLeft()>$this->writeLength) {
			if($i==0) {
				$send = $header.$sender->getSendData($this->writeLength-9);
				$this->write($send);
				$i++;
			continue;
			}
			$send = $sender->getSendData($this->writeLength);
			$this->write($send);
			$i++;
		}
			if($sender->getSendLeft()!=0) {
				$this->write(self::padRandom($sender->getSendData($sender->getSendLeft()), $this->writeLength));
			}
			$sender->onSendEnd();
	}
	
	function receiveStream(StreamReceiver $receiver) {
		$first = $this->read();
		$type = \IntVal::uint8()->getValue($first[0]);
		
		if($type != self::FILE && $type != self::ERROR) {
			throw new ProtocolMismatchException("Protocol mismatch: expected ProtocolBase::FILE, got ".$type);
		}
		$size = \IntVal::uint64LE()->getValue(substr($first, 1, 8));
		$receiver->setRecvSize($size);
		$receiver->onRecvStart();
		if($size+9<=$this->readLength) {
			$receiver->receiveData(substr($first, 9, $size));
			$receiver->onRecvEnd();
		return;
		}
		$receiver->receiveData(substr($first, 9, $size));
		while($receiver->getRecvLeft()>$this->writeLength) {
			$receiver->receiveData($this->read($this->readLength));
		}
		if($receiver->getRecvLeft()>0) {
			$padded = $this->read($this->readLength);
			$receiver->receiveData(substr($padded, 0, $receiver->getRecvLeft()));
		}
		$receiver->onRecvEnd();
	}
}
