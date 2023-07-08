<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase {
	function __construct() {
		parent::__construct();
	}
	
	static function setUpBeforeClass() {
		TestHelper::createDatabase();
		TestHelper::initServer();
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createText("/readme.txt", "Testing test file");
	}
	
	static function tearDownAfterClass() {
		TestHelper::deleteDatabase();
		TestHelper::deleteStorage();
	}
	
	function setUp() {
		$this->node = Node::fromName(TestHelper::getEPDO(), "test01");
	}
	
	static function getExamplePath() {
		return "/tmp/crow-protect/readme.txt";
	}

	function testInitLocal() {
		$path = $this->getExamplePath();
		$object = new File($path);
		$this->assertInstanceOf(File::class, $object);
	}
	
	function testGetPath() {
		$object = new File($this->getExamplePath());
		$this->assertEquals(self::getExamplePath(), $object->getPath());
	}
	
	function testGetBasenameFile() {
		$object = new File($this->getExamplePath());
		$this->assertEquals("readme.txt", $object->getBasename());
	}

	function testGetDirnameFile() {
		$object = new File($this->getExamplePath());
		$this->assertEquals("/tmp/crow-protect", $object->getDirname());
	}

	
	function testGetATime() {
		$object = new File($this->getExamplePath());
		$this->assertEquals($object->getATime(), fileatime($this->getExamplePath()));
	}

	function testGetMTime() {
		$object = new File($this->getExamplePath());
		$this->assertEquals($object->getMTime(), filemtime($this->getExamplePath()));
	}

	function testGetCTime() {
		$object = new File($this->getExamplePath());
		$this->assertEquals($object->getCTime(), filectime($this->getExamplePath()));
	}
	
	function testGetPerms() {
		$object = new File($this->getExamplePath());
		$this->assertEquals($object->getPerms(), fileperms($this->getExamplePath()));
	}
	
	function testGetOwner() {
		$object = new File($this->getExamplePath());
		$owner = posix_getpwuid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getOwner(), $owner["name"]);
	}
	
	function testGetGroup() {
		$object = new File($this->getExamplePath());
		$group = posix_getgrgid(filegroup($this->getExamplePath()));
		$this->assertEquals($object->getGroup(), $group["name"]);
	}
	
	function testGetSize() {
		$object = new File($this->getExamplePath());
		$this->assertEquals($object->getSize(), filesize($this->getExamplePath()));
	}
	
	function testGetType() {
		$object = new File($this->getExamplePath());
		$this->assertEquals(Catalog::TYPE_FILE, $object->getType());
		$object = new File("/tmp/crow-protect/");
		$this->assertEquals(Catalog::TYPE_DIR, $object->getType());
	}
	
	function testGetBasenameDir() {
		$object = new File("/tmp/crow-protect/");
		$this->assertEquals("crow-protect", $object->getBasename());
	}
	
	function testGetDirnameDir() {
		$object = new File("/tmp/crow-protect/");
		$this->assertEquals("/tmp", $object->getDirname());
	}
	
	function testHasParent() {
		$object = new File("/tmp/crow-protect/");
		$this->assertEquals(TRUE, $object->hasParent());
	}
	
	function testHasNoParent() {
		$object = new File("/tmp/");
		$this->assertEquals(FALSE, $object->hasParent());
	}
	
	function testGetParent() {
		$object = new File($this->getExamplePath());
		$parent = $object->getParent();
		$this->assertEquals("crow-protect", $parent->getBasename());
		$this->assertEquals("/tmp", $parent->getDirname());
	}
	
	function testGetNoParent() {
		$object = new File("/tmp/");
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("File /tmp/ has no parent.");
		$object->getParent();
	}
}
