<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\LinkSender;
class LinkSenderTest extends TestCase {
	const FILESIZE = 27389;
	function setUp() {
		$mockup = new MockupFiles("/tmp/crow-protect/");
		$mockup->createRandom("/FileReader.bin", self::FILESIZE, 1);
		$mockup->createLink("/FileReader.bin", "linkto");
	}
	
	function tearDown() {
		$mockup = new MockupFiles("/tmp/crow-protect/");
		$mockup->delete();
		clearstatcache();
	}
	
	function testConstruct() {
		$sender = new LinkSender(File::fromPath("/tmp/crow-protect/linkto"));
		$this->assertInstanceOf(LinkSender::class, $sender);
	}
	
	function testGetSize() {
		$sender = new LinkSender(File::fromPath("/tmp/crow-protect/linkto"));
		$this->assertEquals(32, $sender->getSendSize());
	}
	
	function testGetData() {
		$sender = new LinkSender(File::fromPath("/tmp/crow-protect/linkto"));
		$this->assertEquals("/tmp/crow-protect/FileReader.bin", $sender->getSendData(32));
	}

	function testGetLeft() {
		$sender = new LinkSender(File::fromPath("/tmp/crow-protect/linkto"));
		$this->assertEquals(32, $sender->getSendLeft());
		$this->assertEquals("/tmp/crow-protect/FileReader.bin", $sender->getSendData(32));
		$this->assertEquals(0, $sender->getSendLeft());
	}

	
}
