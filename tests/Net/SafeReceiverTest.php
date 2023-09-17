<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\SafeReceiver;
class SafeReceiverTest extends TestCase {
	const FILESIZE = 27389;
	static function setUpBeforeClass() {
		mkdir(__DIR__."/example");
		file_put_contents(self::getSource(), random_bytes(self::FILESIZE));
	}
	
	function setUp() {
		if(!file_exists(self::getSource())) {
			file_put_contents(self::getSource(), random_bytes(self::FILESIZE));
		}
	}

	function tearDown() {
		if(file_exists(self::getTarget())) {
			unlink(self::getTarget());
		}
	}

	static function getSource(): string {
		return __DIR__."/example/source.bin";
	}

	static function getTarget(): string {
		return __DIR__."/example/target.bin";
	}
	
	static function tearDownAfterClass() {
		if(file_exists(self::getSource())) {
			unlink(self::getSource());
		}
		if(file_exists(self::getTarget())) {
			unlink(self::getTarget());
		}
		rmdir(__DIR__."/example");
	}
			
	function testConstruct() {
		$receiver = new SafeReceiver(new \Net\StringReceiver(), 1024);
		$this->assertInstanceOf(SafeReceiver::class, $receiver);
		$this->assertEquals(2048, $receiver->getRecvSize());
		$this->assertEquals(2048, $receiver->getRecvLeft());
	}
	
	function testSetSize() {
		$sender = new \Net\StringSender(5, "The cat is on the mat.");
		$receiver = new \Net\StringReceiver();
		
		$header = chr(\Net\Protocol::FILE);
		$header .= \IntVal::uint64LE()->putValue(1024*3);
		$header .= \IntVal::uint64LE()->putValue($sender->getSendSize());
		$control = \Net\Protocol::padRandom($header, 1024);
			
		$receiver = new SafeReceiver($receiver, 1024);
		$receiver->receiveData($control);
		$this->assertEquals(1024*3, $receiver->getRecvSize());
		// The first block has been used up by the data block.
		$this->assertEquals(1024*2, $receiver->getRecvLeft());
	}

	function testGetSmall() {
		$sender = new \Net\StringSender(5, "The cat is on the mat.");
		$mr = new \Net\MockReceiver();
		
		
		$header = chr(\Net\Protocol::FILE);
		$header .= \IntVal::uint64LE()->putValue(1024*3);
		$header .= \IntVal::uint64LE()->putValue($sender->getSendSize());
		$control = \Net\Protocol::padRandom($header, 1024);
			
		$receiver = new SafeReceiver($mr, 1024);
		$this->assertEquals(FALSE, $mr->hasStarted());
		
		$receiver->receiveData($control);
		$this->assertEquals(TRUE, $mr->hasStarted());
		$this->assertEquals(1024*3, $receiver->getRecvSize());
		// The first block has been used up by the data block.
		$this->assertEquals(1024*2, $receiver->getRecvLeft());
		
		$receiver->receiveData($sender->getSendData(1024));
		
		$this->assertEquals("The cat is on the mat.", $mr->getString());
		$this->assertEquals(1024, $receiver->getRecvLeft());

		$receiver->receiveData(\Net\Protocol::getControlBlock(\Net\Protocol::FILE_OK, 1024));
		$this->assertEquals(TRUE, $mr->hasEnded());
		$this->assertEquals(FALSE, $mr->wasCancelled());
	}

	function testOnStart() {
		$sender = new \Net\StringSender(5, "The cat is on the mat.");
		$inner = new \Net\StringReceiver();
		/*
		 * Add data to inner receiver
		 */
		$inner->receiveData("The mouse is in the house.");
		$this->assertEquals("The mouse is in the house.", $inner->getString());
		
		$header = chr(\Net\Protocol::FILE);
		$header .= \IntVal::uint64LE()->putValue(1024*3);
		$header .= \IntVal::uint64LE()->putValue($sender->getSendSize());
		$control = \Net\Protocol::padRandom($header, 1024);
			
		$receiver = new SafeReceiver($inner, 1024);
		$receiver->receiveData($control);
		/**
		 * When the initial control block is received, onStart has to be called
		 * on the inner receiver. StringReceiver::onStart() clears the contents
		 * of StringReceiver.
		 */
		$this->assertEquals("", $inner->getString());
	}
	
	function testGetLarge() {
		$size = 4192;
		$ceiled = \Net\Protocol::ceilBlock($size, 10);
		$blocks = $ceiled/1024;
				
		$random = random_bytes($size);
		$sender = new \Net\StringSender(5, $random);
		$inner = new \Net\StringReceiver();
		
		$header = chr(\Net\Protocol::FILE);
		$header .= \IntVal::uint64LE()->putValue(\Net\Protocol::ceilBlock($size, 10)+2048);
		$header .= \IntVal::uint64LE()->putValue($sender->getSendSize());
		$control = \Net\Protocol::padRandom($header, 1024);
			
		$receiver = new SafeReceiver($inner, 1024);
		$receiver->receiveData($control);
		$this->assertEquals(5120+2048, $receiver->getRecvSize());
		// The first block has been used up by the data block.
		$this->assertEquals(6144, $receiver->getRecvLeft());
		
		for($i=0;$i<$blocks ;$i++) {
			$receiver->receiveData($sender->getSendData(1024));
		}
		
		$this->assertEquals(strlen($random), strlen($inner->getString()));
		$this->assertEquals($random, $inner->getString());
		
		$this->assertEquals(1024, $receiver->getRecvLeft());
		$receiver->receiveData(\Net\Protocol::getControlBlock(\Net\Protocol::FILE_OK, 1024));
		$this->assertEquals(0, $receiver->getRecvLeft());
	}
	
	function testGetXLarge() {
		$size = (1024*1024*10)+517;
		$ceiled = \Net\Protocol::ceilBlock($size, 10);
		$blocks = $ceiled/1024;
				
		$random = random_bytes($size);
		$sender = new \Net\StringSender(5, $random);
		$inner = new \Net\StringReceiver();
		
		$header = chr(\Net\Protocol::FILE);
		$header .= \IntVal::uint64LE()->putValue(\Net\Protocol::ceilBlock($size, 10)+2048);
		$header .= \IntVal::uint64LE()->putValue($sender->getSendSize());
		$control = \Net\Protocol::padRandom($header, 1024);
			
		$receiver = new SafeReceiver($inner, 1024);
		$receiver->receiveData($control);
		$this->assertEquals($ceiled+2048, $receiver->getRecvSize());
		// The first block has been used up by the data block.
		$this->assertEquals($ceiled+1024, $receiver->getRecvLeft());
		
		for($i=0;$i<$blocks ;$i++) {
			$receiver->receiveData($sender->getSendData(1024));
		}
		
		$this->assertEquals(strlen($random), strlen($inner->getString()));
		// It is quite useless to dump 10 MiB of data here if both values do not
		// fit, so compare md5 hashes.
		$this->assertEquals(md5($random), md5($inner->getString()));
		
		$this->assertEquals(1024, $receiver->getRecvLeft());
		$receiver->receiveData(\Net\Protocol::getControlBlock(\Net\Protocol::FILE_OK, 1024));
		$this->assertEquals(0, $receiver->getRecvLeft());
	}

	function testGetXLargeCancelOnEnd() {
		$size = (1024*1024*10)+517;
		$ceiled = \Net\Protocol::ceilBlock($size, 10);
		$blocks = $ceiled/1024;
				
		$random = random_bytes($size);
		$sender = new \Net\StringSender(5, $random);
		$inner = new \Net\MockReceiver();
		
		$header = chr(\Net\Protocol::FILE);
		$header .= \IntVal::uint64LE()->putValue(\Net\Protocol::ceilBlock($size, 10)+2048);
		$header .= \IntVal::uint64LE()->putValue($sender->getSendSize());
		$control = \Net\Protocol::padRandom($header, 1024);
			
		$receiver = new SafeReceiver($inner, 1024);
		$receiver->receiveData($control);
		$this->assertEquals($ceiled+2048, $receiver->getRecvSize());
		// The first block has been used up by the data block.
		$this->assertEquals($ceiled+1024, $receiver->getRecvLeft());
		
		for($i=0;$i<$blocks ;$i++) {
			$receiver->receiveData($sender->getSendData(1024));
		}
		
		$this->assertEquals(strlen($random), strlen($inner->getString()));
		$this->assertEquals(md5($random), md5($inner->getString()));
		
		$this->assertEquals(1024, $receiver->getRecvLeft());
		/**
		 * We send \Net\Protocol::FILE_CANCEL as control block. onCancel is
		 * called, which wipes the contents of the inner receiver.
		 */
		$receiver->receiveData(\Net\Protocol::getControlBlock(\Net\Protocol::FILE_CANCEL, 1024));
		$this->assertEquals(0, $receiver->getRecvLeft());
		//$this->assertEquals("", $inner->getString());
		$this->assertEquals(TRUE, $inner->wasCancelled());
	}
	

}
