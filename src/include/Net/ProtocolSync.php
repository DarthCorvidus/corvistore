<?php
namespace Net;
class ProtocolSync extends Protocol {
	const OK = 1;
	const MESSAGE = 2;
	const COMMAND = 3;
	const SERIALIZED_PHP = 4;
	const FILE = 5;
	const ERROR = 255;
	private $stream;
	private $blockSize = 1024;
	function __construct(Stream $stream) {
		$this->stream = $stream;
	}
	
	private function sendString(int $type, $string) {
		$len = strlen($string);
		$header = chr($type);
		$header .= \IntVal::uint32SE()->putValue($len);
		$padLength = (int)(ceil($len/$this->blockSize))*1024;
		
		$data = parent::padRandom($header.$string, $padLength);
		$sender = new \Net\StringSender($type, $data);
		while($sender->getSendLeft()>0) {
			$this->stream->write($sender->getSendData($this->blockSize));
		}
	}
	
	function sendCommand(string $command) {
		$this->sendString(self::COMMAND, $command);
	}
	
	function sendMessage(string $message) {
		$this->sendString(self::MESSAGE, $message);
	}
	
	function sendSerialize($serialize) {
		$this->sendString(self::SERIALIZED_PHP, serialize($serialize));
	}
	
	private function getString(int $expectedType): string {
		$data = $this->stream->read($this->blockSize);
		$type = ord($data[0]);
		if($type!==$expectedType) {
			throw new ProtocolMismatchException("received type ".$type." does not match expected type ".$expectedType);
		}
		$size = \IntVal::uint32LE()->getValue(substr($data, 1, 4));
		if($size<=$this->blockSize-5) {
			return substr($data, 5, $size);
		}
		$rec = new StringReceiver();
		$rec->setRecvSize($size);
		$rec->receiveData(substr($data, 5, $size));
		
		while($rec->getRecvLeft()>=$this->blockSize) {
			$rec->receiveData($this->stream->read($this->blockSize));
		}
		if($rec->getRecvLeft()>=0) {
			$rest = $rec->getRecvLeft();
			$last = $this->stream->read($this->blockSize);
			$rec->receiveData(substr($last, 0, $rest));
		}
	return $rec->getString();
	}
	
	function getCommand(): string {
		return $this->getString(self::COMMAND);
	}
	
	function getMessage(): string {
		return $this->getString(self::MESSAGE);
	}
	
	function getSerialized() {
		return unserialize($this->getString(self::SERIALIZED_PHP));
	}
	
	public function sendOK() {
		$package = random_bytes(parent::padRandom(chr(self::OK), $this->blockSize-2)).chr(self::OK);
		$this->stream->write($package);
	}
	
	public function getOK() {
		$package = $this->stream->read($this->blockSize);
		$type = ord($package[0]);
		$secType = ord($package[$this->blockSize-1]);
		if($type!=self::OK) {
			throw new ProtocolMismatchException("received type ".$type." does not match expected type ".self::OK);
		}
		if($type!==$secType) {
			throw new ProtocolMismatchException("malformed OK package");
		}
	}
}
