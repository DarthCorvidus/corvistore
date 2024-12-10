<?php
/*
 * Written by Bing/ChatGPT
 */
use PHPUnit\Framework\TestCase;
use Net\MockReceiver;
class MockReceiverTest extends TestCase {
	private MockReceiver $mockReceiver;

	protected function setUp(): void {
		$this->mockReceiver = new MockReceiver();
	}

	public function testRecvSize(): void {
		$this->mockReceiver->setRecvSize(100);
		$this->assertEquals(100, $this->mockReceiver->getRecvSize());
	}

	public function testReceiveData(): void {
		$this->mockReceiver->setRecvSize(100);
		$this->mockReceiver->receiveData("Hello, World!");
		$this->assertEquals(87, $this->mockReceiver->getRecvLeft());
	}

	public function testOnRecvStart(): void {
		$this->mockReceiver->onRecvStart();
		$this->assertTrue($this->mockReceiver->hasStarted());
	}

	public function testOnRecvEnd(): void {
		$this->mockReceiver->onRecvEnd();
		$this->assertTrue($this->mockReceiver->hasEnded());
	}

	public function testOnRecvCancel(): void {
		$this->mockReceiver->onRecvCancel();
		$this->assertTrue($this->mockReceiver->wasCancelled());
	}

	public function testGetString(): void {
		$this->mockReceiver->receiveData("Hello, World!");
		$this->assertEquals("Hello, World!", $this->mockReceiver->getString());
	}
}