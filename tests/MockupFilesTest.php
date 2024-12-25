<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class MockupFilesTest extends TestCase {
	static function setUpBeforeClass(): void {
		if(file_exists("/tmp/crow-protect")) {
			exec("rm /tmp/crow-protect/ -r");
		}
	}
	
	function setUp(): void {
		if(file_exists("/tmp/crow-protect")) {
			exec("rm /tmp/crow-protect/ -r");
		}
	}
	
	static function tearDownAfterClass(): void {
		if(file_exists("/tmp/crow-protect")) {
			exec("rm /tmp/crow-protect/ -r");
		}
	}

	function testConstruct(): void {
		new MockupFiles("/tmp/crow-protect");
		$this->assertFileExists("/tmp/crow-protect");
	}
	
	function testConstructExisting(): void {
		new MockupFiles("/tmp/crow-protect");
		$this->assertFileExists("/tmp/crow-protect");
	}
	
	function testGetInternalPath(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$path = TestHelper::invoke($mockup, "getInternalPath", array("/vacation/2023_thailand/beach.jpg"));
		$this->assertEquals("/tmp/crow-protect/vacation/2023_thailand/beach.jpg", $path);
	}
	
	function testDelete(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->delete();
		$this->assertEquals(FALSE, file_exists("/tmp/crow-protect"));
	}

	function testCreateDir(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createDir("/Pictures/2023/vacation-thailand/");
		$this->assertFileExists("/tmp/crow-protect/Pictures/2023/vacation-thailand");
	}
	
	function testCreateText(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createText("/test.txt", "Hello World!");
		$this->assertFileExists("/tmp/crow-protect/test.txt");
	}
	
	function testDeepCreateText(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createText("/Documents/test.txt", "Hello World!");
		$this->assertFileExists("/tmp/crow-protect/Documents/test.txt");
	}

	function testDeepCreateTextReturnPath(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$dir = $mockup->createText("/Documents/test.txt", "Hello World!");
		$this->assertFileExists("/tmp/crow-protect/Documents/test.txt");
		$this->assertEquals($dir, "/tmp/crow-protect/Documents/test.txt");
	}
	
	function testCreateRandom(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/random.bin", 1024*10);
		$this->assertFileExists("/tmp/crow-protect/random.bin");
		$this->assertEquals(1024*1024*10, filesize("/tmp/crow-protect/random.bin"));
	}

	function testCreateRandomTiny(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/random.bin", 15, 1);
		$this->assertFileExists("/tmp/crow-protect/random.bin");
		$this->assertEquals(15, filesize("/tmp/crow-protect/random.bin"));
	}
	
	function testDeepCreatePath(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$dir = $mockup->createRandom("/images/vacation/random.bin", 1024*10);
		$this->assertFileExists("/tmp/crow-protect/images/vacation/random.bin");
		$this->assertEquals($dir, "/tmp/crow-protect/images/vacation/random.bin");
	}

	function testDeepCreateRandom(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/images/vacation/random.bin", 1024*10);
		$this->assertFileExists("/tmp/crow-protect/images/vacation/random.bin");
		$this->assertEquals(1024*1024*10, filesize("/tmp/crow-protect/images/vacation/random.bin"));
	}
	
	function testClear(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/images/vacation/random01.bin", 1024*10);
		$mockup->createRandom("/images/vacation/random02.bin", 1024*10);
		$mockup->createRandom("/images/vacation/random03.bin", 1024*10);
		$mockup->clear();
		$this->assertFileExists("/tmp/crow-protect");
		$this->assertEquals(FALSE, file_exists("/tmp/crow-protect/images/vacation/random.bin"));
	}
	
	function testDeleteFile(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/images/vacation/random01.bin", 1024*10);
		$mockup->deleteFile("/images/vacation/random01.bin");
		$this->assertEquals(FALSE, file_exists("/tmp/crow-protect/images/vacation/random01.bin"));
	}
	
	function testDeleteRecreate(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/images/vacation/random01.bin", 1024*10);
		$oldStat = stat("/tmp/crow-protect/images/vacation/random01.bin");
		sleep(2);
		$mockup->deleteFile("/images/vacation/random01.bin");
		$mockup->createDir("/images/vacation/random01.bin");
		$newStat = stat("/tmp/crow-protect/images/vacation/random01.bin");
		$this->assertEquals(TRUE, is_dir("/tmp/crow-protect/images/vacation/random01.bin"));
		$this->assertNotEquals($oldStat, $newStat);
	}
	
	function testCreateLink(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/images/vacation/random01.bin", 1024*10);
		$mockup->createLink("/images/vacation/random01.bin", "/linkto");
		$this->assertEquals("/tmp/crow-protect/images/vacation/random01.bin", readlink("/tmp/crow-protect/linkto"));
	}

}