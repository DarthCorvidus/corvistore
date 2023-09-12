<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\StringSender;
use Net\FileSender;
use Net\SafeSender;
class SafeSenderTest extends TestCase {
	const FILESIZE = 27389;
	static function setUpBeforeClass() {
		mkdir(__DIR__."/example");
		file_put_contents(__DIR__."/example/FileReader.bin", random_bytes(self::FILESIZE));
	}
	
	function setUp() {
		if(!file_exists(__DIR__."/example/FileReader.bin")) {
			file_put_contents(__DIR__."/example/FileReader.bin", random_bytes(self::FILESIZE));
		}
	}
	
	static function tearDownAfterClass() {
		if(file_exists(__DIR__."/example/FileReader.bin")) {
			unlink(__DIR__."/example/FileReader.bin");
		}
		rmdir(__DIR__."/example");
	}
			
	function testConstruct() {
		$expected = random_bytes(self::FILESIZE);
		$sender = new SafeSender(new StringSender(5, $expected), 1024);
		$this->assertInstanceOf(SafeSender::class, $sender);
	}
	
	function testGetSize() {
		$expected = random_bytes(self::FILESIZE);
		$sender = new SafeSender(new StringSender(5, $expected), 1024);
		$multiple = \Net\Protocol::ceilBlock(self::FILESIZE, 10);
		$this->assertEquals($multiple+2048, $sender->getSendSize());
	}
	
	function testGetShort() {
		$expected = "The cat is on the mat.";
		$sender = new SafeSender(new StringSender(5, $expected), 1024);
		$first = $sender->getSendData(1024);
		$this->assertEquals(5, ord($first[0]));
		$this->assertEquals(1024*3, \IntVal::uint64LE()->getValue(substr($first, 1, 8)));
		$this->assertEquals(strlen($expected), \IntVal::uint64LE()->getValue(substr($first, 9, 8)));
		$second = $sender->getSendData(1024);
		$this->assertEquals(1024, strlen($second));
		$this->assertEquals($expected, substr($second, 0, strlen($expected)));
		$third = $sender->getSendData(1024);
		$this->assertEquals(1024, strlen($third));
		$this->assertEquals(1, ord($third[0]));
		$this->assertEquals(1, ord($third[1023]));
		$this->assertEquals(0, $sender->getSendLeft());
	}

	function testGetLong() {
		$expected = random_bytes(self::FILESIZE);
		$sender = new SafeSender(new StringSender(5, $expected), 1024);
		$multiple = \Net\Protocol::ceilBlock(self::FILESIZE, 10);
		$this->assertEquals($multiple+2048, $sender->getSendSize());
		$data = "";
		while($sender->getSendLeft()>0) {
			$data .= $sender->getSendData(1024);
		}
		#echo strlen($data)/1024;
		#echo PHP_EOL;
		$this->assertEquals($multiple+2048, strlen($data));
	}
}
