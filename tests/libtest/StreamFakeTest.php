<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
#include __DIR__."/../lib/StreamFake.php";
class StreamFakeTest extends TestCase {
	function testConstruct(): void {
		$sf = new StreamFake("Hello world!");
		$this->assertInstanceOf(StreamFake::class, $sf);
	}
	
	function getData(): void {
		$stream = new StreamFake("Hello world!");
		$this->assertEquals("Hello world!", $stream->getData());
	}
	
	function testRead(): void {
		$expect = random_bytes(1024*10);
		$stream = new StreamFake($expect);
		$read = "";
		for($i=0;$i<10;$i++) {
			$read .= $stream->read(1024);
		}
		$this->assertEquals($expect, $read);
	}

	function testWrite(): void {
		$expect = random_bytes(1024*10);
		$stream = new StreamFake("");
		for($i=0;$i<10;$i++) {
			$written = $stream->write(substr($expect, 1024*$i, 1024));
			$this->assertEquals(1024, $written);
		}
		$this->assertEquals($expect, $stream->getData());
	}
}
