<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Net;

/**
 * Description of ProtocolAsync
 *
 * @author hm
 */
class ProtocolAsync extends Protocol implements HubClientListener {
	private ProtocolAsyncListener $listener;
	private array $expected = array();
	private array $sendStream = array();
	private array $sendListeners = array();
	private StreamReceiver $streamReceiver;
	private ?StreamReceiver $fileReceiver = NULL;
	private ?int $currentRecvType = NULL;
	public function __construct(ProtocolAsyncListener $listener) {
		$this->listener = $listener;
		/**
		 * Some remarks on how StreamReceiver/FileReceiver are related to each
		 * other. $this->streamReceiver is the current StreamReceiver used. It
		 * will be switched to $this->fileReceiver once a file comes in.
		 */
		$this->streamReceiver = new StringReceiver();
	}
	
	function setFileReceiver(StreamReceiver $receiver): void {
		$this->fileReceiver = $receiver;
	}
	
	private function getCurrentSender(): StreamSender {
		return $this->sendStream[0];
	}
	
	/**
	 * This is just a PHP variant of Java's (StringReceiver).
	 * @psalm-suppress MoreSpecificReturnType
	 * @psalm-suppress LessSpecificReturnStatement
	 * @param StreamReceiver $receiver
	 * @return StringReceiver
	 */
	private function toStringReceiver(StreamReceiver $receiver): StringReceiver {
		return $receiver;
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

	public function onDisconnect(): void {
		$this->listener->onDisconnect($this);
	}
	
	private function isString(int $type): bool {
		return in_array($type, array(self::MESSAGE, self::COMMAND, self::SERIALIZED_PHP, self::BINARY_CLASS));
	}

	public function onRead(string $data): void {
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
				$out = substr($data, 5);
				$this->readString($out);
			return;
			}
			if($this->currentRecvType===self::FILE) {
				if($this->fileReceiver === null) {
					throw new \InvalidArgumentException("received file, but no file receiver was defined.");
				}
				$this->streamReceiver = new \Net\SafeReceiver($this->fileReceiver, $this->getPacketLength());
				$this->streamReceiver->receiveData($data);
				#$this->streamReceiver->setRecvSize(\IntVal::uint64LE()->getValue(substr($data, 1, 8)));
				#$this->streamReceiver->onRecvStart();
				#$out = substr($data, 9);
				#$this->readFile($out);
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
		
		if($this->currentRecvType==self::FILE) {
			$this->streamReceiver->receiveData($data);
			if($this->streamReceiver->getRecvLeft()==0) {
				$this->currentRecvType = NULL;
			}
		return;
		}
	}

	public function onWrite(): string {
		$current = $this->getCurrentSender();
		if($this->isString($current->getSendType()) && $current->getSendSize()==$current->getSendLeft()) {
			return $this->onWriteFirstString($current);
		}
		
		if($current->getSendType()==self::OK) {
			return $this->onWriteFirstString($current);
		}
		if($this->isString($current->getSendType())) {
			return $this->onWriteString($current);
		}
		if($current->getSendType()==self::FILE) {
			return $current->getSendData($this->getPacketLength());
		}
	throw new \RuntimeException("Unable to determine data to write.");
	}
	
	private function onWriteFirstString(StreamSender $sender): string {
		$data = chr($sender->getSendType());
		$data .= \IntVal::uint32LE()->putValue($sender->getSendSize());
		$packetLength = $this->getPacketLength();
		if($sender->getSendLeft()<=$packetLength-5) {
			$data .= parent::padRandom($sender->getSendData($sender->getSendLeft()), $packetLength-5);
			#array_shift($this->sendStream);
		return $data;
		}
		$data .= $sender->getSendData($packetLength-5);
	return $data;
	}
	
	private function onWriteFirstFile(StreamSender $sender): string {
		$data = chr($sender->getSendType());
		$data .= \IntVal::uint64LE()->putValue($sender->getSendSize());
		$packetLength = $this->getPacketLength();
		if($sender->getSendLeft()<=$packetLength-9) {
			$data .= parent::padRandom($sender->getSendData($sender->getSendLeft()), $packetLength-9);
			#array_shift($this->sendStream);
		return $data;
		}
		$data .= $sender->getSendData($packetLength-9);
	return $data;
	}
	
	private function onWriteString(StreamSender $sender): string {
		$packetLength = $this->getPacketLength();
		if($sender->getSendLeft()<=$packetLength) {
			$data = parent::padRandom($sender->getSendData($sender->getSendLeft()), $packetLength);
			#array_shift($this->sendStream);
		return $data;
		}
	return $sender->getSendData($packetLength);
	}

	function onWritten(): void {
		/**
		 * If we're at the end of a SendStream, move the stream off the stack
		 * and call the send listener, if there is one (it can be NULL).
		 */
		if($this->sendStream[0]->getSendLeft()<=0) {
			array_shift($this->sendStream);
			$listener = array_shift($this->sendListeners);
			if($listener!=NULL) {
				$listener->onSent($this);
			}
		}
	}

	private function sendString(int $type, string $data, ProtocolSendListener $listener = NULL): void {
		#$this->sendStream[] = new StringSender(chr($type).\IntVal::uint32LE()->putValue(strlen($data)).$data);
		$this->sendStream[] = new StringSender($type, $data);
		$this->sendListeners[] = $listener;
	return;
	}
	
	/**
	 * @psalm-suppress ArgumentTypeCoercion
	 * @param ProtocolSendListener $listener
	 * @return void
	 */
	function sendOK(ProtocolSendListener $listener = NULL): void {
		/**
		 * OK packages are a special form of strings that have the size of a
		 * package, but begin and end with self::OK.
		 */
		$data = random_bytes($this->getPacketLength()-6).chr(self::OK);
		$this->sendStream[] = new StringSender(self::OK, $data);
		$this->sendListeners[] = $listener;
	}
	
	function readOk(string $data): void {
		$last = $data[strlen($data)-1];
		if($last!==chr(self::OK)) {
			throw new \RuntimeException("malformed OK packet.");
		}
		$this->currentRecvType = NULL;
		$this->listener->onOK($this);
	}
	
	private function readString(string $data): void {
		$receiver = $this->getCurrentReceiver();
		$len = strlen($data);
		if($len<$receiver->getRecvLeft()) {
			$receiver->receiveData($data);
		return;
		}
		$receiver->receiveData(substr($data, 0, $receiver->getRecvLeft()));
		$type = $this->currentRecvType;
		$this->currentRecvType = NULL;
		$string = $this->toStringReceiver($this->streamReceiver)->getString();
		if($type==self::MESSAGE) {
			$this->listener->onMessage($this, $string);
		}
		if($type==self::COMMAND) {
			$this->listener->onCommand($this, $string);
		}
		if($type==self::SERIALIZED_PHP) {
			$unserialized = unserialize($string);
			$this->listener->onSerialized($this, $unserialized);
		}
		/*
		 * Unpack a class from binary data using <class>::fromBinary.
		 */
		
		if($type==self::BINARY_CLASS) {
			throw new \RuntimeException("not implemented yet");
			#$br = new \plibv4\Binary\StringReader($string, \plibv4\Binary\StringReader::LE);
			#$classname = $br->getIndexedString(16);
			#$classdata = $br->getIndexedString(32);
			/*
			 * This actually works.
			 */
			#$instance = $classname::fromBinary($classdata);
			#$this->listener->onBinaryClass($this, $instance);
		}

	}
	
	public function sendMessage(string $message, ProtocolSendListener $listener = NULL): void {
		$this->sendString(self::MESSAGE, $message, $listener);
	}

	public function sendCommand(string $message, ProtocolSendListener $listener = NULL): void {
		$this->sendString(self::COMMAND, $message, $listener);
	}

	public function sendSerialize(mixed $serialize, ProtocolSendListener $listener = NULL): void {
		$serialized = serialize($serialize);
		$this->sendString(self::SERIALIZED_PHP, $serialized, $listener);
	}
	
	/*
	 * Pack a class to binary using $class->toBinary(). Currently, no proper
	 * interface like 'Binaryable' exists.
	 */
	public function sendBinaryClass(\BinaryPersistable $instance, ProtocolSendListener $listener = NULL): void {
		$binaryClass = $instance->toBinary();
		$classname = $instance::class;
		$bw = new \plibv4\Binary\StringWriter(\plibv4\Binary\StringWriter::LE);
		$bw->addIndexedString(16, $classname);
		$bw->addIndexedString(32, $binaryClass);
		$this->sendString(self::BINARY_CLASS, $bw->getBinary(), $listener);
	}
	
	public function sendStream(StreamSender $sender, ProtocolSendListener $listener = NULL): void {
		$this->sendStream[] = new SafeSender($sender, $this->getPacketLength());
		$this->sendListeners[] = $listener;
	}
	
	public function expect(int $type): void {
		$this->expected[] = $type;
	}
	
	public function checkExpect(int $type): void {
		if(empty($this->expected)) {
			return;
		}
		$expected = array_shift($this->expected);
		if($type!=$expected) {
			throw new \RuntimeException("Expectation mismatch: expected ".$expected.", got ".$type);
		}
	}
}