<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\SafeReceiver;
use Net\SafeSender;
use Net\StringSender;
use Net\StringReceiver;
class SafeSRTest extends TestCase {
	const FILESIZE = 27389;
	static function setUpBeforeClass() {
		mkdir(__DIR__."/example");
		file_put_contents(self::getSource(), random_bytes(self::FILESIZE));
	}
	
	function setUp() {
		if(!file_exists(self::getSource())) {
			file_put_contents(self::getSource(), random_bytes(self::FILESIZE));
		}
	}

	function tearDown() {
		if(file_exists(self::getTarget())) {
			unlink(self::getTarget());
		}
	}

	static function getSource(): string {
		return __DIR__."/example/source.bin";
	}

	static function getTarget(): string {
		return __DIR__."/example/target.bin";
	}
	
	static function tearDownAfterClass() {
		if(file_exists(self::getSource())) {
			unlink(self::getSource());
		}
		if(file_exists(self::getTarget())) {
			unlink(self::getTarget());
		}
		rmdir(__DIR__."/example");
	}

	function testSendReceiveSmall() {
		$payload = "The cat is on the mat.";
		$sender = new SafeSender(new StringSender(5, $payload), 1024);
		$sr = new StringReceiver();
		$receiver = new SafeReceiver($sr, 1024);
		for($i=0;$i<3;$i++) {
			$receiver->receiveData($sender->getSendData(1024));
		}
		$this->assertEquals(strlen($payload), strlen($sr->getString()));
		$this->assertEquals($payload, $sr->getString());
	}
	
	function testSendReceiveBlock() {
		$payload = random_bytes(1024);
		$sender = new SafeSender(new StringSender(5, $payload), 1024);
		$sr = new StringReceiver();
		$receiver = new SafeReceiver($sr, 1024);
		for($i=0;$i<3;$i++) {
			$receiver->receiveData($sender->getSendData(1024));
		}
		$this->assertEquals(strlen($payload), strlen($sr->getString()));
		$this->assertEquals($payload, $sr->getString());
	}

	function testSendReceiveLarger() {
		$payload = random_bytes(4199);
		$sender = new SafeSender(new StringSender(5, $payload), 1024);
		$sr = new StringReceiver();
		$receiver = new SafeReceiver($sr, 1024);
		for($i=0;$i<5+2;$i++) {
			$receiver->receiveData($sender->getSendData(1024));
		}
		$this->assertEquals(strlen($payload), strlen($sr->getString()));
		$this->assertEquals($payload, $sr->getString());
	}

	function testSendReceiveXLarge() {
		$payload = random_bytes((12*1024*1024)+312);
		$sender = new SafeSender(new StringSender(5, $payload), 1024);
		$sr = new StringReceiver();
		$receiver = new SafeReceiver($sr, 1024);
		for($i=0;$i<12289+2;$i++) {
			$receiver->receiveData($sender->getSendData(1024));
		}
		$this->assertEquals(strlen($payload), strlen($sr->getString()));
		$this->assertEquals($payload, $sr->getString());
	}
}
