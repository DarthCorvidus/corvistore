<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class StorageBasicTest extends TestCase {
	static function setUpBeforeClass() {
		TestHelper::resetDatabase();
		TestHelper::resetStorage();
	}
	
	function testDefine() {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);
		$command = new CommandParser("define storage backup-main02 type=basic location=".__DIR__."/storage/basic02");
		StorageBasic::define(TestHelper::getEPDO(), $command);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_storage", "dst_id");
		$target[0] = array("dst_id" => 1, "dst_name" => "backup-main01", "dst_location"=>__DIR__."/storage/basic01", "dst_type"=>"basic");
		$target[1] = array("dst_id" => 2, "dst_name" => "backup-main02", "dst_location"=>__DIR__."/storage/basic02", "dst_type"=>"basic");
		$this->assertEquals($target, $database);
	}

	function testUnique() {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		$this->expectException(Exception::class);
		StorageBasic::define(TestHelper::getEPDO(), $command);
	}
	
	function testFromArray() {
		$array = TestHelper::getEPDO()->row("select * from d_storage where dst_id = ?", array(1));
		$storage = StorageBasic::fromArray(TestHelper::getEPDO(), $array);
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}
	
	function testFromName() {
		$storage = Storage::fromName(TestHelper::getEPDO(), "backup-main01");
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}
	
	function testFromNameBogus() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Storage 'bogus' not available");
		$storage = Storage::fromName(TestHelper::getEPDO(), "bogus");
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}

	
	function testFromId() {
		$storage = Storage::fromId(TestHelper::getEPDO(), 1);
		$this->assertInstanceOf(StorageBasic::class, $storage);
		$this->assertEquals("backup-main01", $storage->getName());
	}

	function testFromIdBogus() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Storage with id '37' not available");
		$storage = Storage::fromId(TestHelper::getEPDO(), 37);
	}
	
	
	
	function testGetName() {
		$storage = Storage::fromName(TestHelper::getEPDO(), "backup-main01");
		$this->assertEquals("backup-main01", $storage->getName());
	}
	
	function testGetId() {
		$storage = Storage::fromName(TestHelper::getEPDO(), "backup-main02");
		$this->assertEquals("2", $storage->getId());
	}
	
	function testGetHexArray() {
		$hex = StorageBasic::getHexArray(37177506666152);
		$target = array("00", "00", "21", "d0", "10", "14", "16", "a8");
		$this->assertEquals($target, $hex);
	}
	
	function testGetPathForIdFile() {
		$shared = new Shared();
		$shared->useSQLite(__DIR__."/test.sqlite");
		$pdo = $shared->getEPDO();
		$storage = StorageBasic::fromName(TestHelper::getEPDO(), "backup-main01");
		$target = __DIR__."/storage/basic01/00/00/21/d0/10/14/16/a8.cp";
		$this->assertEquals($target, $storage->getPathForIdFile(37177506666152));
	}

	function testGetPathForIdLocation() {
		$shared = new Shared();
		$shared->useSQLite(__DIR__."/test.sqlite");
		$pdo = $shared->getEPDO();
		$storage = StorageBasic::fromName(TestHelper::getEPDO(), "backup-main01");
		$target = __DIR__."/storage/basic01/00/00/21/d0/10/14/16/";
		$this->assertEquals($target, $storage->getPathForIdLocation(37177506666152));
	}
	
	function testStore() {
		TestHelper::initServer();
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$partition = $node->getPolicy()->getPartition();
		$storage = Storage::fromId(TestHelper::getEPDO(), $partition->getStorageId());
		
		$files = new MockupFiles("/tmp/crow-protect");
		$files->createRandom("image01.bin", 12);
		$source = new SourceObject($node, "/tmp/crow-protect/image01.bin");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = $catalog->loadcreate($source);
		$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		$versionEntry = $versions->addVersion($source);
		$storage->store($versionEntry, $partition, $source);
		$this->assertFileExists(__DIR__."/storage/basic01/00/00/00/00/00/00/00/01.cp");
		$this->assertEquals(md5_file("/tmp/crow-protect/image01.bin"), md5_file(__DIR__."/storage/basic01/00/00/00/00/00/00/00/01.cp"));
	}
	
	#function testRestore() {
	#	$node = Node::fromName(TestHelper::getEPDO(), "test01");
	#	$storage = Storage::fromId(TestHelper::getEPDO(), $node->getPolicy()->getPartition()->getStorageId());
	#	$storage->restore($entry, $target)
	#	
	#}
}
