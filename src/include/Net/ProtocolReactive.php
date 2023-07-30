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
	const ERROR = 255;
	private $sendStack = array();
	private $listener = array();
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
		$type = ord($data[0]);
		if($type==self::MESSAGE) {
			$this->readString($type, $data);
		}
		if($type==self::COMMAND) {
			$this->readString($type, $data);
		}

	}

	public function onWrite(string $name, int $id): string {
		return array_shift($this->sendStack);
	}

	public function sendString(int $type, string $data) {
		$len = strlen($data);
		/*
		 * If the string length is below the packet length + 5 bytes overhead,
		 * use sendShortString.
		 */
		if(strlen($data)<$this->getPacketLength("default", 0)+5) {
			$this->sendShortString($type, $data, $len);
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
		$string = substr($data, 5, $length);
		if($type==self::MESSAGE) {
			$this->listener->onMessage($this, $string);
		}
		if($type==self::COMMAND) {
			$this->listener->onCommand($this, $string);
		}
		
	}

}
