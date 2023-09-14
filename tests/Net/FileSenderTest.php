<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\FileSender;
class FileSenderTest extends TestCase {
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
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$this->assertInstanceOf(FileSender::class, $sender);
	}
	
	function testGetSize() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$this->assertEquals(self::FILESIZE, $sender->getSendSize());
	}
	
	function testStartStop() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$this->assertEquals(NULL, $sender->onSendStart());
		$this->assertEquals(NULL, $sender->onSendEnd());
	}
	
	function testStartRemovedFile() {
		$file = new File(__DIR__."/example/FileReader.bin");
		$sender = new FileSender($file);
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("unable to open ".$file->getPath()." for read.");
		unlink($file->getPath());
		$sender->onSendStart();
	}

	function testReadRemovedFile() {
		$file = new File(__DIR__."/example/FileReader.bin");
		$sender = new FileSender($file);
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("File went away during transfer");
		$sender->onSendStart();
		unlink($file->getPath());
		$sender->getSendData(1024);
	}

	function testReadChangedFile() {
		$file = new File(__DIR__."/example/FileReader.bin");
		$sender = new FileSender($file);
		
		$sender->onSendStart();
		$fh = fopen(__DIR__."/example/FileReader.bin", "a");
		fwrite($fh, "XYZ");
		fclose($fh);
		clearstatcache();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("Filesize has changed during transfer");
		$sender->getSendData(1024);
	}
	
	
	
	function testRead() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$sender->onSendStart();
		$rest = 27389;
		$total = 27389;
		$i=0;
		$contents = "";
		while($rest>4096) {
			$load = $sender->getSendData(4096);
			$contents .= $load;
			$this->assertEquals($load, file_get_contents(__DIR__."/example/FileReader.bin", FALSE, NULL, $i*4096, 4096));
			$rest -= 4096;
			$i++;
		}
		$load = file_get_contents(__DIR__."/example/FileReader.bin", FALSE, NULL, $i*4096, $rest);
		$this->assertEquals($load, $sender->getSendData($rest));
		
		$sender->onSendEnd();
	}
	
	function testRemoveBeforeStart() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		unlink(__DIR__."/example/FileReader.bin");
		$this->expectException(RuntimeException::class);
		$sender->onSendStart();
		
	}
	
	function testRemoveBeforeRead() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$sender->onSendStart();
		$sender->onSendEnd();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Resource for ".__DIR__."/example/FileReader.bin went away.");
		$sender->getSendData(4096);
	}
	
	function testReadTooMuch() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$sender->onSendStart();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Unable to read expected amount from ".__DIR__."/example/FileReader.bin, expected 27400, got 27389");
		$sender->getSendData(27400);
		$sender->onSendEnd();
	}
	
	function testGetLeft() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$sender->onSendStart();
		$sender->getSendData(4096);
		$this->assertEquals(self::FILESIZE-4096, $sender->getSendLeft());
	}

}
