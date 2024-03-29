<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\MockSender;
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
		$ms = new MockSender($expected);
		$sender = new SafeSender($ms, 1024);
		$this->assertInstanceOf(SafeSender::class, $sender);
		$this->assertEquals(FALSE, $ms->hasEnded());
		$this->assertEquals(FALSE, $ms->hasStarted());
	}
	
	function testGetSize() {
		$expected = random_bytes(self::FILESIZE);
		$ms = new MockSender($expected);
		$sender = new SafeSender($ms, 1024);
		$multiple = \Net\Protocol::ceilBlock(self::FILESIZE, 10);
		$this->assertEquals($multiple+2048, $sender->getSendSize());
		$this->assertEquals(FALSE, $ms->hasEnded());
		$this->assertEquals(FALSE, $ms->hasStarted());
	}
	
	function testGetShort() {
		$expected = "The cat is on the mat.";
		$ms = new MockSender($expected);
		$sender = new SafeSender($ms, 1024);

		$first = $sender->getSendData(1024);
		$this->assertEquals(5, ord($first[0]));
		$this->assertEquals(1024*3, \IntVal::uint64LE()->getValue(substr($first, 1, 8)));
		$this->assertEquals(strlen($expected), \IntVal::uint64LE()->getValue(substr($first, 9, 8)));
		/**
		 * We call onStart before the datablock is sent.
		 */
		$this->assertEquals(FALSE, $ms->hasEnded());
		$this->assertEquals(TRUE, $ms->hasStarted());
		
		/**
		 * We call onEnd before the control block is sent.
		 */
		$second = $sender->getSendData(1024);
		$this->assertEquals(1024, strlen($second));
		$this->assertEquals($expected, substr($second, 0, strlen($expected)));
		$this->assertEquals(TRUE, $ms->hasEnded());
		$this->assertEquals(TRUE, $ms->hasStarted());
		

		/**
		 * We send the control block. It will change according to the result
		 * of onSend (more exact: whether an Exception was thrown or not.
		 */
		$third = $sender->getSendData(1024);
		$this->assertEquals(1024, strlen($third));
		$this->assertEquals(1, ord($third[0]));
		$this->assertEquals(1, ord($third[1023]));
		$this->assertEquals(0, $sender->getSendLeft());
		$this->assertEquals(TRUE, $ms->hasEnded());
		$this->assertEquals(TRUE, $ms->hasStarted());
	}

	function testGetLong() {
		$expected = random_bytes(self::FILESIZE);
		$sender = new SafeSender(new MockSender($expected), 1024);
		$multiple = \Net\Protocol::ceilBlock(self::FILESIZE, 10);
		$this->assertEquals($multiple+2048, $sender->getSendSize());
		$data = "";
		for($i=0;$i<27+2;$i++) {
			$data .= $sender->getSendData(1024);
		}
		#echo strlen($data)/1024;
		#echo PHP_EOL;
		$this->assertEquals($multiple+2048, strlen($data));
		$this->assertEquals($expected, substr($data, 1024, self::FILESIZE));
		$this->assertEquals(\Net\Protocol::FILE_OK, \Net\Protocol::determineControlBlock(substr($data, $multiple+1024)));
	}
	
	function testExceptionOnStart() {
		$expected = random_bytes(self::FILESIZE);
		$ms = new MockSender($expected);
		$ms->setExceptionAfter(0);
		$sender = new SafeSender($ms, 1024);
		$first = $sender->getSendData(1024);
		$this->assertEquals(TRUE, $ms->hasStarted());
		$this->assertEquals(2048, $sender->getSendSize());
		$this->assertEquals(1024, $sender->getSendLeft());
		$this->assertEquals(TRUE, $ms->wasCancelled());
		$last = $sender->getSendData(1024);
		$this->assertEquals(\Net\Protocol::FILE_CANCEL, \Net\Protocol::determineControlBlock($last));
		$this->assertEquals(0, $sender->getSendLeft());
	}

	function testGetCancel() {
		$expected = random_bytes(self::FILESIZE);
		$ms = new MockSender($expected);
		$ms->setExceptionAfter(8192);
		$sender = new SafeSender($ms, 1024);
		$multiple = \Net\Protocol::ceilBlock(self::FILESIZE, 10);
		$this->assertEquals($multiple+2048, $sender->getSendSize());
		$data = "";
		for($i=0;$i<27+2;$i++) {
			$data .= $sender->getSendData(1024);
		}
		#echo strlen($data)/1024;
		#echo PHP_EOL;
		$this->assertEquals($multiple+2048, strlen($data));
		$this->assertEquals(TRUE, $ms->wasCancelled());
		/**
		 * The payload is the same up to 8192 bytes (we have top offset $data
		 * by 1024 because of the header.
		 */
		$this->assertEquals(substr($expected, 0, 8192), substr($data, 1024, 8192));
		/**
		 * After that, it is different.
		 */
		$this->assertNotEquals(substr($expected, 8192, 1024), substr($data, 8192+1024, 1024));
		/**
		 * The control block indicates failure.
		 */
		$this->assertEquals(\Net\Protocol::FILE_CANCEL, \Net\Protocol::determineControlBlock(substr($data, $multiple+1024)));
	}
	
	
}
