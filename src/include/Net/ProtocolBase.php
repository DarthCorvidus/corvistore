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
	const SERIALIZED_OBJ = 4;
	const SERIALIZED_ARRAY = 5;
	const ERROR = 255;
	function __construct($socket, $readLength = 1024, $writeLength = 1024) {
		$this->socket = $socket;
		$this->readLength = $readLength;
		$this->writeLength = $writeLength;
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
		$length = \IntVal::uint16LE()->putValue($trlen);
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
		
		$length = \IntVal::uint16LE()->getValue($first[1].$first[2]);
		$result = "";
		if($length<$this->readLength) {
			$result = substr($first, 3, $length);
			if($readType==self::ERROR) {
				throw new ProtocolErrorException($result);
			}
			return substr($first, 3, $length);
		}
		$result = substr($first, 3, $length);
		$rest = ($length+3)-$this->readLength;
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
}
