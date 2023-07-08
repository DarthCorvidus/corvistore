<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\FileSender;
class FileSenderTest extends TestCase {
	static function setUpBeforeClass() {
		mkdir(__DIR__."/example");
		file_put_contents(__DIR__."/example/FileReader.bin", random_bytes(27389));
	}
	
	function setUp() {
		if(!file_exists(__DIR__."/example/FileReader.bin")) {
			file_put_contents(__DIR__."/example/FileReader.bin", random_bytes(27389));
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
		$this->assertEquals(27389, $sender->getSize());
	}
	
	function testStartStop() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$this->assertEquals(NULL, $sender->onStart());
		$this->assertEquals(NULL, $sender->onEnd());
	}
	
	function testRead() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$sender->onStart();
		$rest = 27389;
		$total = 27389;
		$i=0;
		$contents = "";
		while($rest>4096) {
			$load = $sender->getData(4096);
			$contents .= $load;
			$this->assertEquals($load, file_get_contents(__DIR__."/example/FileReader.bin", FALSE, NULL, $i*4096, 4096));
			$rest -= 4096;
			$i++;
		}
		$load = file_get_contents(__DIR__."/example/FileReader.bin", FALSE, NULL, $i*4096, $rest);
		$this->assertEquals($load, $sender->getData($rest));
		
		$sender->onEnd();
	}
	
	function testRemoveBeforeStart() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		unlink(__DIR__."/example/FileReader.bin");
		$this->expectException(RuntimeException::class);
		$sender->onStart();
		
	}
	
	function testRemoveBeforeRead() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$sender->onStart();
		$sender->onEnd();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Resource for ".__DIR__."/example/FileReader.bin went away.");
		$sender->getData(4096);
	}
	
	function testReadTooMuch() {
		$sender = new FileSender(new File(__DIR__."/example/FileReader.bin"));
		$sender->onStart();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Unable to read expected amount from ".__DIR__."/example/FileReader.bin, expected 27400, got 27389");
		$sender->getData(27400);
		$sender->onEnd();
	}

}
