<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase {
	static function setUpBeforeClass(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->createText("/readme.txt", "Testing test file");
		$mockup->createLink("/readme.txt", "/linkto");
	}
	
	static function tearDownAfterClass(): void {
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->delete();
	}
	
	static function getExamplePath(): string {
		return "/tmp/crow-protect/readme.txt";
	}

	function testInitLocal(): void {
		$path = $this->getExamplePath();
		$object = File::fromPath($path);
		$this->assertInstanceOf(File::class, $object);
	}
	
	function testGetPath(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals(self::getExamplePath(), $object->getPath());
	}
	
	function testGetBasenameFile(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals("readme.txt", $object->getBasename());
	}

	function testGetDirnameFile(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals("/tmp/crow-protect", $object->getDirname());
	}

	
	function testGetATime(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getATime(), fileatime($this->getExamplePath()));
	}

	function testGetMTime(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getMTime(), filemtime($this->getExamplePath()));
	}

	function testGetCTime(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getCTime(), filectime($this->getExamplePath()));
	}
	
	function testGetPerms(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getPerms(), fileperms($this->getExamplePath()));
	}
	
	function testGetOwner(): void {
		$object = File::fromPath($this->getExamplePath());
		$owner = posix_getpwuid(fileowner($this->getExamplePath()));
		$this->assertEquals($object->getOwner(), $owner["name"]);
	}
	
	function testGetGroup(): void {
		$object = File::fromPath($this->getExamplePath());
		$group = posix_getgrgid(filegroup($this->getExamplePath()));
		$this->assertEquals($object->getGroup(), $group["name"]);
	}
	
	function testGetSize(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals($object->getSize(), filesize($this->getExamplePath()));
	}
	
	function testGetType(): void {
		$object = File::fromPath($this->getExamplePath());
		$this->assertEquals(Catalog::TYPE_FILE, $object->getType());
		$object = File::fromPath("/tmp/crow-protect/");
		$this->assertEquals(Catalog::TYPE_DIR, $object->getType());
	}
	
	function testGetBasenameDir(): void {
		$object = File::fromPath("/tmp/crow-protect/");
		$this->assertEquals("crow-protect", $object->getBasename());
	}
	
	function testGetDirnameDir(): void {
		$object = File::fromPath("/tmp/crow-protect/");
		$this->assertEquals("/tmp", $object->getDirname());
	}
	
	function testHasParent(): void {
		$object = File::fromPath("/tmp/crow-protect/");
		$this->assertEquals(TRUE, $object->hasParent());
	}
	
	function testHasNoParent(): void {
		$object = File::fromPath("/tmp/");
		$this->assertEquals(FALSE, $object->hasParent());
	}
	
	function testGetParent(): void {
		$object = File::fromPath($this->getExamplePath());
		$parent = $object->getParent();
		$this->assertEquals("crow-protect", $parent->getBasename());
		$this->assertEquals("/tmp", $parent->getDirname());
	}
	
	function testGetNoParent(): void {
		$object = File::fromPath("/tmp/");
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("File /tmp/ has no parent.");
		$object->getParent();
	}
	
	function testSetGetAction(): void {
		$object = File::fromPath("/tmp/");
		$object->setAction(File::UPDATE);
		$this->assertEquals(File::UPDATE, $object->getAction());
	}
	
	function testSetInvalidAction(): void {
		$object = File::fromPath("/tmp/");
		$this->expectException(Exception::class);
		$object->setAction(25);
	}
	
	function testGetLinkType(): void {
		$object = File::fromPath("/tmp/crow-protect/linkto");
		$this->assertEquals(Catalog::TYPE_LINK, $object->getType());
	}
	
	function testGetLinkTarget(): void {
		$object = File::fromPath("/tmp/crow-protect/linkto");
		$this->assertEquals("/tmp/crow-protect/readme.txt", $object->getTarget());
	}
	
	function testBinary(): void {
		$object = File::fromPath("/tmp/crow-protect/readme.txt");
		$binary = $object->toBinary();
		$object2 = File::fromBinary($binary);
		$this->assertEquals($object, $object2);
		//$this->assertEquals(4736, strlen($binary));
	}

	function testBinaryExtended(): void {
		$object = File::fromPath("/tmp/crow-protect/readme.txt");
		$object->setServerNodeName("ulysses");
		$object->setServerStoreType(File::BACK_MAIN);
		$object->setServerVersionId(73291);
		$object->setServerCreated(time());
		$binary = $object->toBinary();
		$object2 = File::fromBinary($binary);
		$this->assertEquals($object, $object2);
		//$this->assertEquals(4736, strlen($binary));
	}

}
