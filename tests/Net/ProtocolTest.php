<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\Protocol;
class ProtocolTest extends TestCase {
	const FILE_SIZE = 17201;
	function tearDown() {
		if(file_exists(self::getExamplePath())) {
			unlink(self::getExamplePath());
		}
	}
	
	static function getExamplePath(): string {
		return __DIR__."/example.bin";
	}
	
	function testPadRandom() {
		$expected = "The cat is on the mat";
		$block = Protocol::padRandom($expected, 4096);
		$this->assertEquals(4096, strlen($block));
		$this->assertEquals($expected, substr($block, 0, 21));
	}
	
	function testGetControlBlock() {
		for($i=0;$i<=255;$i++) {
			$block = Protocol::getControlBlock($i, 1024);
			$this->assertEquals(1024, strlen($block));
			$this->assertEquals($i, ord($block[0]));
			$this->assertEquals($i, ord($block[1023]));
		}
	}
	
	function testDetermineControlBlock() {
		for($i=0;$i<=255;$i++) {
			$block = Protocol::getControlBlock($i, 1024);
			$this->assertEquals($i, Protocol::determineControlBlock($block));
		}
	}
	
	function testMalformedControlBlock() {
		$block = "The cat is on the mat.";
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("malformed control block, 84 does not equal 46");
		Protocol::determineControlBlock($block);
	}
}
