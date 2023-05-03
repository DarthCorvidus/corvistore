<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class MockupFilesTest extends TestCase {
	function __construct() {
		parent::__construct();
	}
	static function setUpBeforeClass() {
		if(file_exists("/tmp/crow-protect")) {
			exec("rm /tmp/crow-protect/ -r");
		}
	}
	
	function setUp() {
		if(file_exists("/tmp/crow-protect")) {
			exec("rm /tmp/crow-protect/ -r");
		}
	}
	
	static function tearDownAfterClass() {
		if(file_exists("/tmp/crow-protect")) {
			exec("rm /tmp/crow-protect/ -r");
		}
	}

	function testConstruct() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$this->assertFileExists("/tmp/crow-protect");
	}
	
	function testConstructExisting() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$this->assertFileExists("/tmp/crow-protect");
	}
	
	function testDelete() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->delete();
		$this->assertEquals(FALSE, file_exists("/tmp/crow-protect"));
	}
	
	function testCreateText() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createText("/test.txt", "Hello World!");
		$this->assertFileExists("/tmp/crow-protect/test.txt");
	}
	
	function testDeepCreateText() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createText("/Documents/test.txt", "Hello World!");
		$this->assertFileExists("/tmp/crow-protect/Documents/test.txt");
	}
	
	function testCreateRandom() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/random.bin", 1024*10);
		$this->assertFileExists("/tmp/crow-protect/random.bin");
		$this->assertEquals(1024*1024*10, filesize("/tmp/crow-protect/random.bin"));
	}

	function testCreateRandomTiny() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/random.bin", 15, 1);
		$this->assertFileExists("/tmp/crow-protect/random.bin");
		$this->assertEquals(15, filesize("/tmp/crow-protect/random.bin"));
	}
	
	function testDeepCreateRandom() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/images/vacation/random.bin", 1024*10);
		$this->assertFileExists("/tmp/crow-protect/images/vacation/random.bin");
		$this->assertEquals(1024*1024*10, filesize("/tmp/crow-protect/images/vacation/random.bin"));
	}
	
	function testClear() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createRandom("/images/vacation/random.bin", 1024*10);
		$mockup->clear();
		$this->assertFileExists("/tmp/crow-protect");
		$this->assertEquals(FALSE, file_exists("/tmp/crow-protect/images/vacation/random.bin"));
	}
}