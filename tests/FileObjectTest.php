<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class FileObjectTest extends TestCase {
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
		$object = FileObject::fromLocal("example", $path);
		$this->assertInstanceOf(FileObject::class, $object);
	}
	
	function testGetPath() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$this->assertEquals(self::getExamplePath(), $object->getPath());
	}

	function testGetStaging() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$this->assertEquals(self::getExamplePath(), $object->getStaging());
	}
	
	function testGetATime() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$this->assertEquals($object->getATime(), fileatime($this->getExamplePath()));
	}

	function testGetMTime() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$this->assertEquals($object->getMTime(), filemtime($this->getExamplePath()));
	}

	function testGetCTime() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$this->assertEquals($object->getCTime(), filectime($this->getExamplePath()));
	}
	
	function testGetPerms() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$this->assertEquals($object->getPerms(), substr(sprintf('%o', fileperms($this->getExamplePath())), -4));
	}
	
	function testGetOwner() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$owner = posix_getpwuid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getOwner(), $owner["name"]);
	}
	
	function testGetGroup() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$group = posix_getgrgid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getGroup(), $group["name"]);
	}
	
	function testGetOriginalSize() {
		$object = FileObject::fromLocal("example", $this->getExamplePath());
		$this->assertEquals($object->getOriginalSize(), filesize($this->getExamplePath()));
	}

}
