<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\Protocol;
class ProtocolTest extends TestCase implements \Net\ProtocolAsyncListener {
	const FILE_SIZE = 17201;
	private $lastString;
	private $lastSerialized;
	function tearDown() {
		if(file_exists(self::getExamplePath())) {
			unlink(self::getExamplePath());
		}
		$this->lastSerialized = NULL;
		$this->lastString = NULL;
	}
	
	static function getExamplePath(): string {
		return __DIR__."/example.bin";
	}
	
	function testPadRandom() {
		$expected = "The cat is on the mat";
		$block = Protocol::padRandom($expected, 4096);
		$this->assertEquals(4096, strlen($block));
		$this->assertEquals($expected, substr($block, 0, 21));
	}
	
	function testGetControlBlock() {
		for($i=0;$i<=255;$i++) {
			$block = Protocol::getControlBlock($i, 1024);
			$this->assertEquals(1024, strlen($block));
			$this->assertEquals($i, ord($block[0]));
			$this->assertEquals($i, ord($block[1023]));
		}
	}
	
	function testDetermineControlBlock() {
		for($i=0;$i<=255;$i++) {
			$block = Protocol::getControlBlock($i, 1024);
			$this->assertEquals($i, Protocol::determineControlBlock($block));
		}
	}
	
	function testMalformedControlBlock() {
		$block = "The cat is on the mat.";
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("malformed control block, 84 does not equal 46");
		Protocol::determineControlBlock($block);
	}
	
	function testAsyncSyncSendString() {
		$sf = new StreamFake("");
		$async = new Net\ProtocolAsync($this);
		$sync = new Net\ProtocolSync($sf);
		
		$async->sendCommand("quit");
		while($async->hasWrite()) {
			$sf->write($async->onWrite());
			$async->onWritten();
		}
		$command = $sync->getCommand();
		$this->assertEquals("quit", $command);
	}

	function testSyncAsyncSendString() {
		$sf = new StreamFake("");
		$async = new Net\ProtocolAsync($this);
		$sync = new Net\ProtocolSync($sf);
		
		$sync->sendCommand("quit");
		while(!$sf->eof()) {
			$async->onRead($sf->read(1024));
		}
		$this->assertEquals("quit", $this->lastString);
	}

	function testAsyncSyncSendSerialized() {
		$expected = $_SERVER;
		$sf = new StreamFake("");
		$async = new Net\ProtocolAsync($this);
		$sync = new Net\ProtocolSync($sf);
		
		$async->sendSerialize($expected);
		while($async->hasWrite()) {
			$sf->write($async->onWrite());
			$async->onWritten();
		}
		$unserialized = $sync->getSerialized();
		$this->assertEquals($expected, $unserialized);
	}

	function testSyncAsyncSendSerialized() {
		$expected = $_SERVER;
		$sf = new StreamFake("");
		$async = new Net\ProtocolAsync($this);
		$sync = new Net\ProtocolSync($sf);
		
		$sync->sendSerialize($expected);
		while(!$sf->eof()) {
			$async->onRead($sf->read(1024));
		}
		$this->assertEquals($expected, $this->lastSerialized);
	}
	
	function testAsyncSyncSendStream() {
		$expected = random_bytes(self::FILE_SIZE);
		$sf = new StreamFake("");
		$async = new Net\ProtocolAsync($this);
		$sync = new Net\ProtocolSync($sf);
		
		$async->sendStream(new \Net\StringSender(\Net\Protocol::FILE, $expected));
		while($async->hasWrite()) {
			$sf->write($async->onWrite());
			$async->onWritten();
		}
		$sr = new \Net\StringReceiver();
		$sync->getStream($sr);
		$this->assertEquals($expected, $sr->getString());
		$this->assertEquals(self::FILE_SIZE, strlen($sr->getString()));
	}

	function testSyncAsyncSendStream() {
		$expected = random_bytes(self::FILE_SIZE);
		$sf = new StreamFake("");
		
		$async = new Net\ProtocolAsync($this);
		$sr = new \Net\StringReceiver();
		$async->setFileReceiver($sr);
		
		$sync = new Net\ProtocolSync($sf);
		$sync->sendStream(new \Net\StringSender(\Net\Protocol::FILE, $expected));
		while(!$sf->eof()) {
			$async->onRead($sf->read(1024));
		}
		$this->assertEquals($expected, $sr->getString());
	}
	
	
	public function onCommand(\Net\ProtocolAsync $protocol, string $command) {
		$this->lastString = $command;
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol) {
		
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message) {
		
	}

	public function onOk(\Net\ProtocolAsync $protocol) {
		
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, $unserialized) {
		$this->lastSerialized = $unserialized;
	}

}
