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
	const ERROR = 255;
	private $sendStack = array();
	private $listener = array();
	private $rest = 0;
	private $expected = array();
	private $sendStream = array();
	private $streamReceiver = NULL;
	private $currentRecvType = NULL;
	public function __construct(ProtocolReactiveListener $listener) {
		$this->listener = $listener;
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
	
	public function getBinary(string $name, int $id): bool {
		return true;
	}

	public function getPacketLength(string $name, int $id): int {
		return 1024;
	}
	
	#public function getStackSize(): int {
	#	return count($this->sendStack);
	#}

	public function hasWrite(string $name, int $id): bool {
		return !empty($this->sendStream);
	}

	public function onDisconnect(string $name, int $id) {
		$this->listener->onDisconnect($this);
	}
	
	private function isString(int $type) {
		return in_array($type, array(self::MESSAGE, self::COMMAND, self::SERIALIZED_PHP));
	}

	public function onRead(string $name, int $id, string $data) {
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
	}

	public function onWrite(string $name, int $id): string {
		$current = $this->getCurrentSender();
		$left = $current->getSendLeft();
		$length = $this->getPacketLength($name, $id);
		if($left<=$length) {
			$data = $current->getSendData($left);
			array_shift($this->sendStream);
			return self::padRandom($data, $length);
		}
	return $current->getSendData($length);
	}

	private function sendString(int $type, string $data) {
		$this->sendStream[] = new StringSender(chr($type).\IntVal::uint32LE()->putValue(strlen($data)).$data);
	return;
	}
	
	function sendOK() {
		$data = chr(self::OK).random_bytes($this->getPacketLength("x", 0)-2).chr(self::OK);
		$this->sendStream[] = new StringSender($data);
	}
	
	function readOk(string $data) {
		$last = $data[strlen($data)-1];
		if($last!==chr(self::OK)) {
			throw new RuntimeException("malformed OK packet.");
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
