<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Storage\ExtentMain;
class ExtentMainTest extends TestCase {
	const FILESIZE = 27389;
	static function setUpBeforeClass() {
		file_put_contents(self::getPath(), random_bytes(self::FILESIZE));
	}
	
	static function tearDownAfterClass() {
		if(file_exists(self::getPath())) {
			unlink(self::getPath());
		}
	}

	static function getPath(): string {
		return __DIR__."/example.bin";
	}

	function testFromInstance() {
		$file = new File(self::getPath());
		$extent = ExtentMain::fromFile($file, "testnode");
		$this->assertInstanceOf(ExtentMain::class, $extent);
	}
	
	function testGetTotalSize() {
		$file = new File(self::getPath());
		$extent = ExtentMain::fromFile($file, "testnode");
		$this->assertEquals($file->getSize(), $extent->getTotalSize());
	}
	
	function testGetVersion() {
		$file = new File(self::getPath());
		$extent = ExtentMain::fromFile($file, "testnode");
		$this->assertEquals(1, $extent->getVersion());
	}
	
	function testGetMtime() {
		$file = new File(self::getPath());
		$extent = ExtentMain::fromFile($file, "testnode");
		$this->assertEquals($file->getMTime(), $extent->getMtime());
	}
	
	function testToBinary() {
		$file = new File(self::getPath());
		$extent = ExtentMain::fromFile($file, "testnode");
		$this->assertEquals(4914, strlen($extent->toBinary()));
	}
	
	function testFromBinary() {
		$file = new File(self::getPath());
		$extent = ExtentMain::fromFile($file, "testnode");
		$new = ExtentMain::fromBinary($extent->toBinary());
		$this->assertEquals($extent, $new);
	}
}
