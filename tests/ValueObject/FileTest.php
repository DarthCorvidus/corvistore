<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase {
	function __construct() {
		parent::__construct();
	}
	
	static function setUpBeforeClass() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createText("/readme.txt", "Testing test file");
		$mockup->createLink("/readme.txt", "/linkto");
	}
	
	static function tearDownAfterClass() {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->delete();
	}
	
	static function getExamplePath() {
		return "/tmp/crow-protect/readme.txt";
	}

	function testInitLocal() {
		$path = $this->getExamplePath();
		$object = File::fromPath($path);
		$this->assertInstanceOf(File::class, $object);
	}
	
	function testGetPath() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals(self::getExamplePath(), $object->getPath());
	}
	
	function testGetBasenameFile() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals("readme.txt", $object->getBasename());
	}

	function testGetDirnameFile() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals("/tmp/crow-protect", $object->getDirname());
	}

	
	function testGetATime() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getATime(), fileatime($this->getExamplePath()));
	}

	function testGetMTime() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getMTime(), filemtime($this->getExamplePath()));
	}

	function testGetCTime() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getCTime(), filectime($this->getExamplePath()));
	}
	
	function testGetPerms() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getPerms(), fileperms($this->getExamplePath()));
	}
	
	function testGetOwner() {
		$object = File::fromPath($this->getExamplePath());
		$owner = posix_getpwuid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getOwner(), $owner["name"]);
	}
	
	function testGetGroup() {
		$object = File::fromPath($this->getExamplePath());
		$group = posix_getgrgid(filegroup($this->getExamplePath()));
		$this->assertEquals($object->getGroup(), $group["name"]);
	}
	
	function testGetSize() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getSize(), filesize($this->getExamplePath()));
	}
	
	function testGetType() {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals(Catalog::TYPE_FILE, $object->getType());
		$object = File::fromPath("/tmp/crow-protect/");
		$this->assertEquals(Catalog::TYPE_DIR, $object->getType());
	}
	
	function testGetBasenameDir() {
		$object = File::fromPath("/tmp/crow-protect/");
		$this->assertEquals("crow-protect", $object->getBasename());
	}
	
	function testGetDirnameDir() {
		$object = File::fromPath("/tmp/crow-protect/");
		$this->assertEquals("/tmp", $object->getDirname());
	}
	
	function testHasParent() {
		$object = File::fromPath("/tmp/crow-protect/");
		$this->assertEquals(TRUE, $object->hasParent());
	}
	
	function testHasNoParent() {
		$object = File::fromPath("/tmp/");
		$this->assertEquals(FALSE, $object->hasParent());
	}
	
	function testGetParent() {
		$object = File::fromPath($this->getExamplePath());
		$parent = $object->getParent();
		$this->assertEquals("crow-protect", $parent->getBasename());
		$this->assertEquals("/tmp", $parent->getDirname());
	}
	
	function testGetNoParent() {
		$object = File::fromPath("/tmp/");
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("File /tmp/ has no parent.");
		$object->getParent();
	}
	
	function testSetGetAction() {
		$object = File::fromPath("/tmp/");
		$object->setAction(FILE::UPDATE);
		$this->assertEquals(FILE::UPDATE, $object->getAction());
	}
	
	function testSetInvalidAction() {
		$object = File::fromPath("/tmp/");
		$this->expectException(Exception::class);
		$object->setAction(25);
	}
	
	function testGetLinkType() {
		$object = File::fromPath("/tmp/crow-protect/linkto");
		$this->assertEquals(Catalog::TYPE_LINK, $object->getType());
	}
	
	function testGetLinkTarget() {
		$object = File::fromPath("/tmp/crow-protect/linkto");
		$this->assertEquals("/tmp/crow-protect/readme.txt", $object->getTarget());
	}
	
	function testBinary() {
		$object = File::fromPath("/tmp/crow-protect/readme.txt");
		$binary = $object->toBinary();
		$object2 = File::fromBinary($binary);
		$this->assertEquals($object, $object2);
		$this->assertEquals(4654, strlen($binary));
	}

}
