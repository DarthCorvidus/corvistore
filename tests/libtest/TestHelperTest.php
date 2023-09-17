<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class TestHelperTest extends TestCase {
	function testBinaryAsHex() {
		$bin = chr(9).chr(255).chr(0).chr(10);
		$hex = "0x09ff000a";
		$this->assertEquals($hex, TestHelper::binaryAsHex($bin));
	}
}
