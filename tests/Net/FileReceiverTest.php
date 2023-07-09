<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\FileReceiver;
class FileReceiverTest extends TestCase {
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
		$receiver = new FileReceiver(self::getTarget());
		$this->assertInstanceOf(FileReceiver::class, $receiver);
	}
	
	function testReceiveLeft() {
		$data = file_get_contents(self::getSource());
		$receiver = new FileReceiver(self::getTarget());
		$receiver->setRecvSize(filesize(self::getSource()));
		$receiver->onRecvStart();
		$this->assertEquals(self::FILESIZE, $receiver->getRecvLeft());
		$receiver->receiveData(substr($data, 0, 4096));
		$this->assertEquals(self::FILESIZE-4096, $receiver->getRecvLeft());
		
	}
	
	function testReceiveFile() {
		$source = file_get_contents(self::getSource());
		$receiver = new FileReceiver(self::getTarget());
		$receiver->setRecvSize(filesize(self::getSource()));
		$receiver->onRecvStart();
		$pos = 0;
		$i=0;
		while($receiver->getRecvLeft()>1024) {
			$receiver->receiveData(substr($source, $i*1024, 1024));
			$pos = $pos + 1024;
			$i++;
		}
		if($receiver->getRecvLeft()!=0) {
			$receiver->receiveData(substr($source, $i*1024, $receiver->getRecvLeft()));
		}
		$receiver->onRecvEnd();
		$target = file_get_contents(self::getTarget());
		$this->assertEquals(self::FILESIZE, strlen($target));
		$this->assertEquals($source, $target);
	}
	
	function testReceiveTooMuch() {
		$source = file_get_contents(self::getSource());
		$receiver = new FileReceiver(self::getTarget());
		$receiver->setRecvSize(filesize(self::getSource()));
		$receiver->onRecvStart();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("expected filesize exceeded by 1 bytes");
		$receiver->receiveData(random_bytes(self::FILESIZE+1));
		$receiver->onRecvEnd();
	}
}
