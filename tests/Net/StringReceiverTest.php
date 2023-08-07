<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\StringReceiver;
class StringReceiverTest extends TestCase {
	function testConstruct() {
		$sr = new StringReceiver();
		$this->assertInstanceOf(StringReceiver::class, $sr);
	}
	
	function testGetSize() {
		$sr = new StringReceiver();
		$sr->setRecvSize(7325);
		$this->assertEquals(7325, $sr->getRecvSize());
	}

	function testReceiveDataShort() {
		$expected = "Hello World!";
		$sr = new StringReceiver();
		$sr->setRecvSize(12);
		$sr->receiveData($expected);
		$this->assertEquals($expected, TestHelper::getPropertyValue($sr, "string"));
	}

	function testReceiveDataShortPadded() {
		$expected = "Hello World!";
		$sr = new StringReceiver();
		$sr->setRecvSize(12);
		$sr->receiveData($expected.random_bytes(1024-12));
		$this->assertEquals($expected, TestHelper::getPropertyValue($sr, "string"));
	}
	
	function testReceiveDataLongPadded() {
		$array = array();
		for($i=0;$i<1024;$i++) {
			$array[] = "Value ".str_pad(dechex($i), 4, "0", STR_PAD_LEFT);
		}
		$serialized = serialize($array);
		$len = strlen($serialized);
		$rounded = (int)(ceil($len/1024)*1024);
		$blocks = $rounded/1024;
		$padded = $serialized.random_bytes($rounded-$len);
		$sr = new StringReceiver();
		$sr->setRecvSize($len);
		for($i=0;$i<$blocks;$i++) {
			$sr->receiveData(substr($padded, 1024*$i, 1024));
		}
		$this->assertEquals(0, $sr->getRecvLeft());
		$this->assertEquals($serialized, TestHelper::getPropertyValue($sr, "string"));
	}
}