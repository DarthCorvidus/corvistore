<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
#include __DIR__."/../lib/StreamFake.php";
class StreamFakeTest extends TestCase {
	function testConstruct() {
		$sf = new StreamFake("Hello world!");
		$this->assertInstanceOf(StreamFake::class, $sf);
	}
	
	function getData() {
		$stream = new StreamFake("Hello world!");
		$this->assertEquals("Hello world!", $stream->getData());
	}
	
	function testRead() {
		$expect = random_bytes(1024*10);
		$stream = new StreamFake($expect);
		$read = "";
		for($i=0;$i<10;$i++) {
			$read .= $stream->read(1024);
		}
		$this->assertEquals($expect, $read);
	}

	function testWrite() {
		$expect = random_bytes(1024*10);
		$stream = new StreamFake("");
		for($i=0;$i<10;$i++) {
			$stream->write(substr($expect, 1024*$i, 1024));
		}
		$this->assertEquals($expect, $stream->getData());
	}
}
