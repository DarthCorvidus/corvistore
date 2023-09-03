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
	
	function sendStream(StreamSender $stream) {
		$header = chr(self::FILE);
		$header .= \IntVal::uint64LE()->putValue($stream->getSendSize());
		$stream->onSendStart();
		/*
		 * Quit early if the file size is block sized - 9, as it can be sent in
		 * one turn.
		 */
		if($stream->getSendSize()<=$this->blockSize-9) {
			$data = $header.$stream->getSendData($stream->getSendSize());
			$this->stream->write(parent::padRandom($data, $this->blockSize));
			$stream->onSendEnd();
		return;
		} else {
			$data = $header.$stream->getSendData($this->blockSize-9);
			$this->stream->write($data);
		}
		/*
		 * Send as long as there are more bytes than one block.
		 */
		while($stream->getSendLeft()>$this->blockSize) {
			$data = $stream->getSendData($this->blockSize);
			$this->stream->write($data);
		}
		/*
		 * If there are bytes left, pad them to the block size and write them.
		 */
		if($stream->getSendLeft()>0) {
			$data = $stream->getSendData($stream->getSendLeft());
			$this->stream->write(parent::padRandom($data, $this->blockSize));
		}
		$stream->onSendEnd();
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
	
	public function getStream(\Net\StreamReceiver $receiver) {
		$data = $this->stream->read($this->blockSize);
		$type = ord($data[0]);
		if($type!==self::FILE) {
			throw new ProtocolMismatchException("received type ".$type." does not match expected type ".self::FILE);
		}
		$size = \IntVal::uint64LE()->getValue(substr($data, 1, 8));
		$receiver->setRecvSize($size);
		$receiver->onRecvStart();
		if($size<=$this->blockSize-9) {
			$receiver->receiveData(substr($data, 9, $size));
			$receiver->onRecvEnd();
		return;
		}
		$receiver->setRecvSize($size);
		$receiver->onRecvStart();
		$receiver->receiveData(substr($data, 9, $size));
		
		while($receiver->getRecvLeft()>=$this->blockSize) {
			$receiver->receiveData($this->stream->read($this->blockSize));
		}
		if($receiver->getRecvLeft()>=0) {
			$rest = $receiver->getRecvLeft();
			$last = $this->stream->read($this->blockSize);
			$receiver->receiveData(substr($last, 0, $rest));
			$receiver->onRecvEnd();
		} else {
			$receiver->onRecvEnd();
		}
	}
}
