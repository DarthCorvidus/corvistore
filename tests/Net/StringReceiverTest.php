<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\StringReceiver;
class StringReceiverTest extends TestCase {
	function testConstruct(): void {
		$sr = new StringReceiver();
		$this->assertInstanceOf(StringReceiver::class, $sr);
	}
	
	function testGetSize(): void {
		$sr = new StringReceiver();
		$sr->setRecvSize(7325);
		$this->assertEquals(7325, $sr->getRecvSize());
	}

	function testReceiveDataShort(): void {
		$expected = "Hello World!";
		$sr = new StringReceiver();
		$sr->setRecvSize(12);
		$sr->receiveData($expected);
		$this->assertEquals($expected, $sr->getString());
	}
	
	function testCancel(): void {
		$expected = "Hello World!";
		$sr = new StringReceiver();
		$sr->receiveData($expected);
		$this->assertEquals($expected, $sr->getString());
		$sr->onRecvCancel();
		$this->assertEquals("", $sr->getString());
	}
	
	function testStart(): void {
		$expected = "Hello World!";
		$sr = new StringReceiver();
		$sr->receiveData($expected);
		$this->assertEquals($expected, $sr->getString());
		$sr->onRecvStart();
		$this->assertEquals("", $sr->getString());
	}

}