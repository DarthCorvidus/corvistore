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
	}
	
	static function getExamplePath() {
		return __DIR__."/source/readme.txt";
	}
	
	function testInitLocal() {
		$path = $this->getExamplePath();
		$object = new SourceObject("example", $path);
		$this->assertInstanceOf(SourceObject::class, $object);
	}
	
	function testGetPath() {
		$object = new SourceObject("example", $this->getExamplePath());
		$this->assertEquals(self::getExamplePath(), $object->getPath());
	}

	function testGetATime() {
		$object = new SourceObject("example", $this->getExamplePath());
		$this->assertEquals($object->getATime(), fileatime($this->getExamplePath()));
	}

	function testGetMTime() {
		$object = new SourceObject("example", $this->getExamplePath());
		$this->assertEquals($object->getMTime(), filemtime($this->getExamplePath()));
	}

	function testGetCTime() {
		$object = new SourceObject("example", $this->getExamplePath());
		$this->assertEquals($object->getCTime(), filectime($this->getExamplePath()));
	}
	
	function testGetPerms() {
		$object = new SourceObject("example", $this->getExamplePath());
		$this->assertEquals($object->getPerms(), substr(sprintf('%o', fileperms($this->getExamplePath())), -4));
	}
	
	function testGetOwner() {
		$object = new SourceObject("example", $this->getExamplePath());
		$owner = posix_getpwuid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getOwner(), $owner["name"]);
	}
	
	function testGetGroup() {
		$object = new SourceObject("example", $this->getExamplePath());
		$group = posix_getgrgid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getGroup(), $group["name"]);
	}
	
	function testGetSize() {
		$object = new SourceObject("example", $this->getExamplePath());
		$this->assertEquals($object->getSize(), filesize($this->getExamplePath()));
	}
	
	function testGetType() {
		$object = new SourceObject("example", $this->getExamplePath());
		$this->assertEquals(SourceObject::TYPE_FILE, $object->getType());
		$object = new SourceObject("example", __DIR__."/storage/");
		$this->assertEquals(SourceObject::TYPE_DIR, $object->getType());
	}

}
