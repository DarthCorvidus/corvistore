<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class SourceObjectTest extends TestCase {
	function __construct() {
		parent::__construct();
		$this->now = mktime();
	}
	
	static function setUpBeforeClass() {
		file_put_contents(self::getExamplePath(), "Crow Protect - Data Storage Solution");
		TestHelper::initServer();
	}
	
	function setUp() {
		$this->node = Node::fromName(TestHelper::getEPDO(), "test01");
	}
	
	static function getExamplePath() {
		return __DIR__."/source/readme.txt";
	}
	
	function testInitLocal() {
		$path = $this->getExamplePath();
		$object = new SourceObject($this->node, $path);
		$this->assertInstanceOf(SourceObject::class, $object);
	}
	
	function testGetPath() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals(self::getExamplePath(), $object->getPath());
	}
	
	function testGetBasenameFile() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals("readme.txt", $object->getBasename());
	}

	function testGetDirnameFile() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals(__DIR__."/source", $object->getDirname());
	}

	
	function testGetATime() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals($object->getATime(), fileatime($this->getExamplePath()));
	}

	function testGetMTime() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals($object->getMTime(), filemtime($this->getExamplePath()));
	}

	function testGetCTime() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals($object->getCTime(), filectime($this->getExamplePath()));
	}
	
	function testGetPerms() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals($object->getPerms(), substr(sprintf('%o', fileperms($this->getExamplePath())), -4));
	}
	
	function testGetOwner() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$owner = posix_getpwuid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getOwner(), $owner["name"]);
	}
	
	function testGetGroup() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$group = posix_getgrgid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getGroup(), $group["name"]);
	}
	
	function testGetSize() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals($object->getSize(), filesize($this->getExamplePath()));
	}
	
	function testGetType() {
		$object = new SourceObject($this->node, $this->getExamplePath());
		$this->assertEquals(Catalog::TYPE_FILE, $object->getType());
		$object = new SourceObject($this->node, __DIR__."/storage/");
		$this->assertEquals(Catalog::TYPE_DIR, $object->getType());
	}
	
	function testGetBasenameDir() {
		$object = new SourceObject($this->node, __DIR__."/storage/");
		$this->assertEquals("storage", $object->getBasename());
	}
	
	function testGetDirnameDir() {
		$object = new SourceObject($this->node, __DIR__."/storage/");
		$this->assertEquals(__DIR__, $object->getDirname());
	}
	
	function testGetNode() {
		$object = new SourceObject($this->node, __DIR__."/storage/");
		$this->assertEquals("test01", $object->getNode()->getName());
	}
	
	function testHasParent() {
		$object = new SourceObject($this->node, __DIR__."/storage/");
		$this->assertEquals(TRUE, $object->hasParent());
	}
	
	function testHasNoParent() {
		$object = new SourceObject($this->node, "/tmp/");
		$this->assertEquals(FALSE, $object->hasParent());
	}
	
	function testGetParent() {
		$object = new SourceObject($this->node, __DIR__."/storage/basic01");
		$parent = $object->getParent();
		$this->assertEquals("storage", $parent->getBasename());
		$this->assertEquals(__DIR__, $parent->getDirname());
	}
	
	function testGetNoParent() {
		$object = new SourceObject($this->node, "/tmp/");
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("SourceObject /tmp has no parent");
		$object->getParent();
	}


}
