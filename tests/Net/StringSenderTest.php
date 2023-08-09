<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\StringSender;
class StringSenderTest extends TestCase {
	function testConstruct() {
		$sr = new StringSender(1, "Hello World!");
		$this->assertInstanceOf(StringSender::class, $sr);
	}
	
	function testGetSize() {
		$sr = new StringSender(1, "Hello World!");
		$this->assertEquals(12, $sr->getSendSize());
	}
	
	function testGetData() {
		$sr = new StringSender(1, "Hello World!");
		$this->assertEquals("He", $sr->getSendData(2));
		$this->assertEquals("llo ", $sr->getSendData(4));
		$this->assertEquals("W", $sr->getSendData(1));
		$this->assertEquals("orl", $sr->getSendData(3));
		$this->assertEquals("d!", $sr->getSendData(2));
	}
	
	function testGetLeft() {
		$sr = new StringSender(1, "Hello World!");
		$this->assertEquals("He", $sr->getSendData(2));
		$this->assertEquals(10, $sr->getSendLeft());
		$this->assertEquals("llo ", $sr->getSendData(4));
		$this->assertEquals(6, $sr->getSendLeft());
		$this->assertEquals("W", $sr->getSendData(1));
		$this->assertEquals(5, $sr->getSendLeft());
		$this->assertEquals("orl", $sr->getSendData(3));
		$this->assertEquals(2, $sr->getSendLeft());
		$this->assertEquals("d!", $sr->getSendData(2));
		$this->assertEquals(0, $sr->getSendLeft());
	}
}