<?php
/*
 * Written by Bing/ChatGPT
 */
use PHPUnit\Framework\TestCase;
use Net\MockReceiver;
class MockReceiverTest extends TestCase {
	private $mockReceiver;

	protected function setUp(): void {
		$this->mockReceiver = new MockReceiver();
	}

	public function testRecvSize() {
		$this->mockReceiver->setRecvSize(100);
		$this->assertEquals(100, $this->mockReceiver->getRecvSize());
	}

	public function testReceiveData() {
		$this->mockReceiver->setRecvSize(100);
		$this->mockReceiver->receiveData("Hello, World!");
		$this->assertEquals(87, $this->mockReceiver->getRecvLeft());
	}

	public function testOnRecvStart() {
		$this->mockReceiver->onRecvStart();
		$this->assertTrue($this->mockReceiver->wasStartCalled());
	}

	public function testOnRecvEnd() {
		$this->mockReceiver->onRecvEnd();
		$this->assertTrue($this->mockReceiver->wasEndCalled());
	}

	public function testOnRecvCancel() {
		$this->mockReceiver->onRecvCancel();
		$this->assertTrue($this->mockReceiver->wasCancelCalled());
	}

	public function testGetString() {
		$this->mockReceiver->receiveData("Hello, World!");
		$this->assertEquals("Hello, World!", $this->mockReceiver->getString());
	}
}