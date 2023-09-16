<?php
/*
 * Written by Bing/ChatGPT
 */
use Net\MockSender;
use PHPUnit\Framework\TestCase;
class MockSenderTest extends TestCase {
    public function testSendData() {
        $sender = new MockSender("Hello World!");
        $sender->onSendStart();
        $this->assertTrue($sender->hasStarted());
        $this->assertEquals(1, $sender->getSendType());
        $this->assertEquals(10, $sender->getSendSize());
        $this->assertEquals("Hello", $sender->getSendData(5));
        $this->assertEquals(5, $sender->getSendLeft());
        $sender->onSendEnd();
        $this->assertTrue($sender->hasEnded());
    }

    public function testExceptionAfter() {
        $this->expectException(\RuntimeException::class);
        $sender = new MockSender("Hello World!");
        $sender->setExceptionAfter(5);
        $sender->getSendData(10);
    }

    public function testCancel() {
        $sender = new MockSender("Hello World!");
        $sender->onSendCancel();
        $this->assertTrue($sender->wasCancelled());
    }
}