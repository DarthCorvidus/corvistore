<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Net;

/**
 * Description of ProtocolReactive
 *
 * @author hm
 */
class ProtocolReactive implements HubClientListener {
	const OK = 1;
	const MESSAGE = 2;
	const COMMAND = 3;
	const SERIALIZED_PHP = 4;
	const FILE = 5;
	const ERROR = 255;
	private $sendStack = array();
	private $listener = array();
	private $rest = 0;
	private $expected = array();
	private $sendStream = array();
	private $streamReceiver = NULL;
	private $fileReceiver = NULL;
	private $currentRecvType = NULL;
	public function __construct(ProtocolReactiveListener $listener) {
		$this->listener = $listener;
	}
	
	function setFileReceiver(StreamReceiver $receiver) {
		$this->fileReceiver = $receiver;
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

	private function getCurrentSender(): StreamSender {
		return $this->sendStream[0];
	}

	private function getCurrentReceiver(): StreamReceiver {
		return $this->streamReceiver;
	}
	
	public function getBinary(): bool {
		return true;
	}

	public function getPacketLength(): int {
		return 1024;
	}
	
	#public function getStackSize(): int {
	#	return count($this->sendStack);
	#}

	public function hasWrite(): bool {
		return !empty($this->sendStream);
	}

	public function onDisconnect() {
		$this->listener->onDisconnect($this);
	}
	
	private function isString(int $type) {
		return in_array($type, array(self::MESSAGE, self::COMMAND, self::SERIALIZED_PHP));
	}

	public function onRead(string $data) {
		/*
		 * If $this->currentRecvType is empty: determine message type, start
		 * reading from data.
		 */
		if($this->currentRecvType===NULL) {
			$this->currentRecvType = ord($data[0]);
			$this->checkExpect($this->currentRecvType);
			if($this->isString($this->currentRecvType)) {
				$this->streamReceiver = new StringReceiver();
				$this->streamReceiver->setRecvSize(\IntVal::uint32LE()->getValue(substr($data, 1, 4)));
				$this->readString(substr($data, 5));
			return;
			}
			if($this->currentRecvType===self::FILE) {
				$this->streamReceiver = $this->fileReceiver;
				$this->streamReceiver->setRecvSize(\IntVal::uint64LE()->getValue(substr($data, 1, 8)));
				$this->streamReceiver->onRecvStart();
				$this->readFile(substr($data, 9));
			}
			if($this->currentRecvType===self::OK) {
				$this->readOk($data);
			return;
			}
		return;
		}
		/*
		 * if $this->currentRecvType is set: continue reading from data. 
		 */
		if($this->isString($this->currentRecvType)) {
			$this->readString($data);
		return;
		}
		
		if($this->currentRecvType==self::FILE) {
			$this->readFile($data);
		return;
		}
	}

	public function onWrite(): string {
		$current = $this->getCurrentSender();
		if($this->isString($current->getSendType()) && $current->getSendSize()==$current->getSendLeft()) {
			return $this->onWriteFirstString($current);
		}
		if($current->getSendType()==self::FILE && $current->getSendSize()==$current->getSendLeft()) {
			return $this->onWriteFirstFile($current);
		}
		
		
		if($current->getSendType()==self::OK) {
			return $this->onWriteFirstString($current);
		}
		if($this->isString($current->getSendType())) {
			return $this->onWriteString($current);
		}
		if($current->getSendType()==self::FILE) {
			return $this->onWriteFile($current);
		}
	}
	
	private function onWriteFirstString(StreamSender $sender) {
		$data = chr($sender->getSendType());
		$data .= \IntVal::uint32LE()->putValue($sender->getSendSize());
		$packetLength = $this->getPacketLength("X", 0);
		if($sender->getSendLeft()<=$packetLength-5) {
			$data .= self::padRandom($sender->getSendData($sender->getSendLeft()), $packetLength-5);
			array_shift($this->sendStream);
		return $data;
		}
		$data .= $sender->getSendData($packetLength-5);
	return $data;
	}
	
	private function onWriteFirstFile(StreamSender $sender) {
		$data = chr($sender->getSendType());
		$data .= \IntVal::uint64LE()->putValue($sender->getSendSize());
		$packetLength = $this->getPacketLength();
		if($sender->getSendLeft()<=$packetLength-9) {
			$data .= self::padRandom($sender->getSendData($sender->getSendLeft()), $packetLength-9);
			array_shift($this->sendStream);
		return $data;
		}
		$data .= $sender->getSendData($packetLength-9);
	return $data;
	}
	
	private function onWriteString(StreamSender $sender) {
		$packetLength = $this->getPacketLength();
		if($sender->getSendLeft()<=$packetLength) {
			$data = self::padRandom($sender->getSendData($sender->getSendLeft()), $packetLength);
			array_shift($this->sendStream);
		return $data;
		}
	return $sender->getSendData($packetLength);
	}

	private function onWriteFile(StreamSender $sender) {
		$packetLength = $this->getPacketLength();
		if($sender->getSendLeft()<=$packetLength) {
			$data = self::padRandom($sender->getSendData($sender->getSendLeft()), $packetLength);
			array_shift($this->sendStream);
		return $data;
		}
	return $sender->getSendData($packetLength);
	}
	

	private function sendString(int $type, string $data) {
		#$this->sendStream[] = new StringSender(chr($type).\IntVal::uint32LE()->putValue(strlen($data)).$data);
		$this->sendStream[] = new StringSender($type, $data);
	return;
	}
	
	function sendOK() {
		/**
		 * OK packages are a special form of strings that have the size of a
		 * package, but begin and end with self::OK.
		 */
		$data = random_bytes($this->getPacketLength("x", 0)-6).chr(self::OK);
		$this->sendStream[] = new StringSender(self::OK, $data);
	}
	
	function readOk(string $data) {
		$last = $data[strlen($data)-1];
		if($last!==chr(self::OK)) {
			throw new \RuntimeException("malformed OK packet.");
		}
		$this->currentRecvType = NULL;
		$this->listener->onOK($this);
	}
	
	private function readString(string $data) {
		$this->getCurrentReceiver()->receiveData($data);
		if($this->getCurrentReceiver()->getRecvLeft()>0) {
			return;
		}
		$type = $this->currentRecvType;
		$this->currentRecvType = NULL;
		if($type==self::MESSAGE) {
			$this->listener->onMessage($this, $this->streamReceiver->getString());
		}
		if($type==self::COMMAND) {
			$this->listener->onCommand($this, $this->streamReceiver->getString());
		}
		if($type==self::SERIALIZED_PHP) {
			$unserialized = unserialize($this->streamReceiver->getString());
			$this->listener->onSerialized($this, $unserialized);
		}
	}
	
	private function readFile(string $data) {
		$current = $this->getCurrentReceiver();
		if($current->getRecvLeft()<$this->getPacketLength()) {
			// The last packet has to be cut off.
			$current->receiveData(substr($data, 0, $current->getRecvLeft()));
			// As we are done, we notify the receiver, delete it and return
			$this->getCurrentReceiver()->onRecvEnd();
			$this->currentRecvType = NULL;
			return;
		} else {
			$current->receiveData($data);
		}
		// continue if packages are left.
		if($current->getRecvLeft()>0) {
		return;
		}
		// End if nothing is left.
		$this->getCurrentReceiver()->onRecvEnd();
		$this->currentRecvType = NULL;
	}
	
	public function sendMessage(string $message) {
		$this->sendString(self::MESSAGE, $message);
	}

	public function sendCommand(string $message) {
		$this->sendString(self::COMMAND, $message);
	}

	public function sendSerialize($serialize) {
		$serialized = serialize($serialize);
		$this->sendString(self::SERIALIZED_PHP, $serialized);
	}
	
	public function sendFile(StreamSender $sender) {
		$this->sendStream[] = $sender;
		$sender->onSendStart();
	}
	
	public function expect(int $type) {
		$this->expected[] = $type;
	}
	
	public function checkExpect(int $type) {
		if(empty($this->expected)) {
			return;
		}
		$expected = array_shift($this->expected);
		if($type!=$expected) {
			throw new \RuntimeException("Expectation mismatch: expected ".$expected.", got ".$type);
		}
	}
}
