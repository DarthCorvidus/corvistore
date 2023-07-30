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

	
	public function getBinary(string $name, int $id): bool {
		return true;
	}

	public function getPacketLength(string $name, int $id): int {
		return 1024;
	}

	public function hasWrite(string $name, int $id): bool {
		return !empty($this->sendStack);
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
		if($type==self::MESSAGE) {
			$this->readString($type, $data);
		}
		if($type==self::COMMAND) {
			$this->readString($type, $data);
		}
		if($type==self::SERIALIZED_PHP) {
			$this->readString($type, $data);
		}
	}

	public function onWrite(string $name, int $id): string {
		return array_shift($this->sendStack);
	}

	private function sendString(int $type, string $data) {
		$len = strlen($data);
		/*
		 * If the string length is below the packet length + 5 bytes overhead,
		 * use sendShortString.
		 */
		if(strlen($data)<$this->getPacketLength("default", 0)+5) {
			$this->sendShortString($type, $data, $len);
		return;
		}
		$data = \IntVal::uint8()->putValue($type).\IntVal::uint32LE()->putValue($len).$data;
		$total = $len+5;
		$packets = floor($total/$this->getPacketLength("", 0));
		$rest = $total % $this->getPacketLength("", 0);
		$packetLength = $this->getPacketLength("", 0);
		for($i=0;$i<$packets;$i++) {
			$this->sendStack[] = substr($data, $i*$packetLength, $packetLength);
		}
		if($rest!=0) {
			$this->sendStack[] = self::padRandom(substr($data, $i*$packetLength, $packetLength), $packetLength);
		}
	}
	
	private function sendShortString(int $type, string $data, int $len) {
		$packet = \IntVal::uint8()->putValue($type);
		$packet .= \IntVal::uint32LE()->putValue($len);
		$packet .= $data;
		$this->sendStack[] = self::padRandom($packet, $this->getPacketLength("", 0));
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
}
