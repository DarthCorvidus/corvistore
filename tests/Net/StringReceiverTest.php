<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\StringReceiver;
class StringReceiverTest extends TestCase {
	function testConstruct() {
		$sr = new StringReceiver();
		$this->assertInstanceOf(StringReceiver::class, $sr);
	}
	
	function testGetSize() {
		$sr = new StringReceiver();
		$sr->setRecvSize(7325);
		$this->assertEquals(7325, $sr->getRecvSize());
	}

	function testReceiveDataShort() {
		$expected = "Hello World!";
		$sr = new StringReceiver();
		$sr->setRecvSize(12);
		$sr->receiveData($expected);
		$this->assertEquals($expected, $sr->getString());
	}
	
	function testCancel() {
		$expected = "Hello World!";
		$sr = new StringReceiver();
		$sr->receiveData($expected);
		$this->assertEquals($expected, $sr->getString());
		$sr->onRecvCancel();
		$this->assertEquals("", $sr->getString());
	}
	
	function testStart() {
		$expected = "Hello World!";
		$sr = new StringReceiver();
		$sr->receiveData($expected);
		$this->assertEquals($expected, $sr->getString());
		$sr->onRecvStart();
		$this->assertEquals("", $sr->getString());
	}

}