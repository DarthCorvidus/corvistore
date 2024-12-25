<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class StorageBasicTest extends TestCase {
	function setUp(): void {
		TestHelper::createDatabase();
		TestHelper::createStorage();
	}
	
	function tearDown(): void {
		TestHelper::deleteStorage();
		TestHelper::deleteDatabase();
	}
	
	function testDefine(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);
		$command = new CommandParser("define storage backup-main02 type=basic location=".__DIR__."/storage/basic02");
		StorageBasic::define(TestHelper::getEPDO(), $command);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_storage", "dst_id");
		$target = array();
		$target[0] = array("dst_id" => 1, "dst_name" => "backup-main01", "dst_location"=>__DIR__."/storage/basic01", "dst_type"=>"basic");
		$target[1] = array("dst_id" => 2, "dst_name" => "backup-main02", "dst_location"=>__DIR__."/storage/basic02", "dst_type"=>"basic");
		$this->assertEquals($target, $database);
	}

	function testUnique(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);
		$this->expectException(Exception::class);
		StorageBasic::define(TestHelper::getEPDO(), $command);
	}
	
	function testFromArray(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);

		$array = TestHelper::getEPDO()->row("select * from d_storage where dst_id = ?", array(1));
		$storage = StorageBasic::fromArray(TestHelper::getEPDO(), $array);
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}
	
	function testFromName(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);
		$storage = Storage::fromName(TestHelper::getEPDO(), "backup-main01");
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}
	
	function testFromNameBogus(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Storage 'bogus' not available");
		$storage = Storage::fromName(TestHelper::getEPDO(), "bogus");
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}

	
	function testFromId(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);

		$storage = Storage::fromId(TestHelper::getEPDO(), 1);
		$this->assertInstanceOf(StorageBasic::class, $storage);
		$this->assertEquals("backup-main01", $storage->getName());
	}

	function testFromIdBogus(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Storage with id '37' not available");
		Storage::fromId(TestHelper::getEPDO(), 37);
	}
	
	
	
	function testGetName(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);

		$storage = Storage::fromName(TestHelper::getEPDO(), "backup-main01");
		$this->assertEquals("backup-main01", $storage->getName());
	}
	
	function testGetId(): void {
		$command01 = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command01);
		$command02 = new CommandParser("define storage backup-main02 type=basic location=".__DIR__."/storage/basic02");
		StorageBasic::define(TestHelper::getEPDO(), $command02);

		$storage = Storage::fromName(TestHelper::getEPDO(), "backup-main02");
		$this->assertEquals("2", $storage->getId());
	}
	
	function testGetHexArray(): void {
		$hex = StorageBasic::getHexArray(37177506666152);
		$target = array("00", "00", "21", "d0", "10", "14", "16", "a8");
		$this->assertEquals($target, $hex);
	}
	
	function testGetPathForIdFile(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);

		$storage = StorageBasic::fromName(TestHelper::getEPDO(), "backup-main01");
		$target = __DIR__."/storage/basic01/00/00/21/d0/10/14/16/a8.cp";
		/**
		 * @todo This is plain wrong, but ok for now
		 * @psalm-suppress UndefinedMethod
		 */
		$this->assertEquals($target, $storage->getPathForIdFile(37177506666152));
	}

	function testGetPathForIdLocation(): void {
		$command = new CommandParser("define storage backup-main01 type=basic location=".__DIR__."/storage/basic01");
		StorageBasic::define(TestHelper::getEPDO(), $command);

		$storage = StorageBasic::fromName(TestHelper::getEPDO(), "backup-main01");
		$target = __DIR__."/storage/basic01/00/00/21/d0/10/14/16/";
		/**
		 * @todo This is plain wrong, but ok for now
		 * @psalm-suppress UndefinedMethod
		 */
		$this->assertEquals($target, $storage->getPathForIdLocation(37177506666152));
	}
	
	function testStore(): void {
		TestHelper::deleteStorage();
		TestHelper::initServer();
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$partition = $node->getPolicy()->getPartition();
		$storage = Storage::fromId(TestHelper::getEPDO(), $partition->getStorageId());
		
		$files = new MockupFiles("/tmp/crow-protect");
		$files->createRandom("image01.bin", 12);
		$file = File::fromPath("/tmp/crow-protect/image01.bin");
		$catalog = new Catalog(TestHelper::getEPDO(), $node);
		/*
		 * This is not correct, since we create the entry below / instead of
		 * /tmp/crow-protect/, but this is irrelevant for this test.
		 */
		$entry = $catalog->newEntry($file);
		#$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		#$versionEntry = $versions->addVersion($source);
		$sr = $storage->store($entry->getVersions()->getLatest(), $partition, $file);
		$sr->setRecvSize($file->getSize());
		$sr->onRecvStart();
		$fh = fopen($file->getPath(), "r");
		while($sr->getRecvLeft()>0) {
			$data = fread($fh, 1024);
			$sr->receiveData($data);
			$tableVersion = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
			// Testing here that dvs_stored is not set while transfer is running.
			$this->assertEquals(0, $tableVersion[0]["dvs_stored"]);
		}
		$sr->onRecvEnd();
		$this->assertFileExists(__DIR__."/storage/basic01/00/00/00/00/00/00/00/01.cp");
		/**
		 * 
		 */
		$this->assertEquals(md5_file("/tmp/crow-protect/image01.bin"), md5(file_get_contents(__DIR__."/storage/basic01/00/00/00/00/00/00/00/01.cp", false, NULL, 8192)));
		$tableVersion = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
		// Testing here that dvs_stored is set after has completed
		$this->assertEquals(1, $tableVersion[0]["dvs_stored"]);
	}

	function testStoreSingle(): void {
		TestHelper::deleteStorage();
		TestHelper::initServer();
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$partition = $node->getPolicy()->getPartition();
		$storage = Storage::fromId(TestHelper::getEPDO(), $partition->getStorageId());
		
		$files = new MockupFiles("/tmp/crow-protect");
		$files->createRandom("image01.bin", 12);
		$file = File::fromPath("/tmp/crow-protect/image01.bin");
		$catalog = new Catalog(TestHelper::getEPDO(), $node);
		/*
		 * This is not correct, since we create the entry below / instead of
		 * /tmp/crow-protect/, but this is irrelevant for this test.
		 */

		#$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		#$versionEntry = $versions->addVersion($source);
		#$file, $entry->getVersions()->getLatest(), $partition, file_get_contents($file->getPath())
		$storageJob = new \Storage\StorageJob($file, $catalog, $partition, $node, file_get_contents($file->getPath()));
		$storage->storeSingle($storageJob);
		$this->assertFileExists(__DIR__."/storage/basic01/00/00/00/00/00/00/00/01.cp");
		/**
		 * 
		 */
		$this->assertEquals(md5_file("/tmp/crow-protect/image01.bin"), md5(file_get_contents(__DIR__."/storage/basic01/00/00/00/00/00/00/00/01.cp", false, NULL, 8192)));
		$tableVersion = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
		// Testing here that dvs_stored is set after has completed
		$this->assertEquals(1, $tableVersion[0]["dvs_stored"]);
		$tableContent = TestHelper::dumpTable(TestHelper::getEPDO(), "d_content", "dco_id");
		$this->assertEquals(1, $tableContent[0]["dco_stored"]);
	}
	
	#function testRestore(): void {
	#	$node = Node::fromName(TestHelper::getEPDO(), "test01");
	#	$storage = Storage::fromId(TestHelper::getEPDO(), $node->getPolicy()->getPartition()->getStorageId());
	#	$storage->restore($entry, $target)
	#	
	#}
}
