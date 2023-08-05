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

	public function onRead(string $name, int $id, string $data) {
		if($this->rest>0) {
			$this->continueString($data);
		return;
		}
		$type = ord($data[0]);
		$this->checkExpect($type);
		if($type==self::MESSAGE) {
			$this->readString($type, $data);
		}
		if($type==self::COMMAND) {
			$this->readString($type, $data);
		}
		if($type==self::SERIALIZED_PHP) {
			$this->readString($type, $data);
		}
		if($type==self::OK) {
			$this->readOK($data);
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
		$this->sendStream[] = new StringReader(chr($type).\IntVal::uint32LE()->putValue(strlen($data)).$data);
	return;
	}
	
	function sendOK($name, $id) {
		$data = chr(self::OK).random_bytes($this->getPacketLength($name, $id)-2).chr(self::OK);
		$this->sendStream[] = new StringReader($data);
	}
	
	function readOk(string $data) {
		$last = $data[strlen($data)-1];
		if($last!==chr(self::OK)) {
			throw new RuntimeException("malformed OK packet.");
		}
		$this->listener->onOK($this);
	}
	
	private function readString(int $type, string $data) {
		$length = \IntVal::uint32LE()->getValue(substr($data, 1, 4));
		$data = substr($data, 5, $length);
		if($length>$this->getPacketLength("", 0)-5) {
			$this->length = $length;
			$this->data = $data;
			$this->rest = $this->length-($this->getPacketLength("", 0)-5);
			$this->type = $type;
		return;
		}
		/*
		 * If the string is shorter/equals the package size, we can finish it
		 * right here.
		 */
		$this->finishString($type, $data);
	}
	
	/**
	 * Continues to get string data from onRead.
	 * @param string $data
	 * @return type
	 */
	private function continueString(string $data) {
		if($this->rest>=$this->getPacketLength("", 0)) {
			$this->data .= $data;
			$this->rest -= $this->getPacketLength("", 0);
			if($this->rest == 0) {
				$this->finishString($this->type, $this->data);
			}
		return;
		}
		if($this->rest<=$this->getPacketLength("", 0)) {
			$this->data .= substr($data, 0, $this->rest);
			$this->finishString($this->type, $this->data);
		}
	}
	
	/**
	 * Commit finished string data to listener callback, depending on type.
	 * @param int $type
	 * @param type $data
	 */
	private function finishString(int $type, $data) {
		$this->data = "";
		$this->rest = 0;
		if($type==self::MESSAGE) {
			$this->listener->onMessage($this, $data);
		}
		if($type==self::COMMAND) {
			$this->listener->onCommand($this, $data);
		}
		if($type==self::SERIALIZED_PHP) {
			$unserialized = unserialize($data);
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
