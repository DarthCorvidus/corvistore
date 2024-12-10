<?php
/*
 * Written by Bing/ChatGPT
 */
use Net\MockSender;
use PHPUnit\Framework\TestCase;
class MockSenderTest extends TestCase {
    public function testSendData(): void {
        $sender = new MockSender("Hello World!");
        $sender->onSendStart();
        $this->assertTrue($sender->hasStarted());
        $this->assertEquals(\Net\Protocol::FILE, $sender->getSendType());
        $this->assertEquals(12, $sender->getSendSize());
        $this->assertEquals("Hello", $sender->getSendData(5));
        $this->assertEquals(7, $sender->getSendLeft());
        $sender->onSendEnd();
        $this->assertTrue($sender->hasEnded());
    }

    public function testExceptionAfter(): void {
        $sender = new MockSender("Hello World!");
        $sender->setExceptionAfter(5);
        $this->expectException(\RuntimeException::class);
		$sender->getSendData(10);
    }

    public function testCancel(): void {
        $sender = new MockSender("Hello World!");
        $sender->onSendCancel();
        $this->assertTrue($sender->wasCancelled());
    }
}