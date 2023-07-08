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
	
	function sendSerializePHP($serialize) {
		$serialized = serialize($serialize);
		$this->sendString(self::SERIALIZE_PHP, $serialized);
	}
	
	function getSerializedPHP() {
		$unserialized = unserialize($this->getString(self::SERIALIZE_PHP));
	return $unserialized;
	}

	function sendStream(StreamSender $sender) {
		$size = $sender->getSize();
		$header = \IntVal::uint8()->putValue(self::FILE);
		$header .= \IntVal::uint64LE()->putValue($ize);

		if($size+strlen($header)<$this->writeLength) {
			$send = $header;
			$send += $sender->getData($size);
			$this->write($send.random_bytes($this->writeLength-($size+9)));
		}
	}
}
