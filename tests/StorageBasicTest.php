<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class StorageBasicTest extends TestCase {
	private $pdo;
	static function setUpBeforeClass() {
		TestHelper::resetDatabase();
	}
	function setUp() {
		$shared = new Shared();
		$shared->useSQLite(__DIR__."/test.sqlite");
		$this->pdo = $shared->getEPDO();
	}
	
	function testCreate() {
		$storage = new StorageBasic($this->pdo, "backup-main01", __DIR__."/storage/backup-main");
		$storage->create();
		$database = TestHelper::dumpTable($this->pdo, "d_storage", "dst_id");
		$target[0] = array("dst_id" => 1, "dst_name" => "backup-main01", "dst_location"=>__DIR__."/storage/backup-main", "dst_type"=>"basic");
		$this->assertEquals($target, $database);
	}
	
	function testUnique() {
		$shared = new Shared();
		$shared->useSQLite(__DIR__."/test.sqlite");
		$pdo = $shared->getEPDO();
		$storage = new StorageBasic($pdo, "backup-main01", __DIR__."/storage/backup-main");
		$this->expectException(Exception::class);
		$storage->create();
	}
	
	function testDefine() {
		$command = new CommandParser("define storage backup-main02 type=basic location=".__DIR__."/storage/backup-main02");
		StorageBasic::define($this->pdo, $command);
		$database = TestHelper::dumpTable($this->pdo, "d_storage", "dst_id");
		$target[0] = array("dst_id" => 1, "dst_name" => "backup-main01", "dst_location"=>__DIR__."/storage/backup-main", "dst_type"=>"basic");
		$target[1] = array("dst_id" => 2, "dst_name" => "backup-main02", "dst_location"=>__DIR__."/storage/backup-main02", "dst_type"=>"basic");
		$this->assertEquals($target, $database);
	}
	
	function testFromArray() {
		$array = TestHelper::getEPDO()->row("select * from d_storage where dst_id = ?", array(1));
		$storage = StorageBasic::fromArray(TestHelper::getEPDO(), $array);
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}
	
	function testFromName() {
		$storage = Storage::fromName($this->pdo, "backup-main01");
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}
	
	function testFromNameBogus() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Storage 'bogus' not available");
		$storage = Storage::fromName($this->pdo, "bogus");
		$this->assertInstanceOf(StorageBasic::class, $storage);
	}

	
	function testFromId() {
		$storage = Storage::fromId($this->pdo, 1);
		$this->assertInstanceOf(StorageBasic::class, $storage);
		$this->assertEquals("backup-main01", $storage->getName());
	}

	function testFromIdBogus() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Storage with id '37' not available");
		$storage = Storage::fromId($this->pdo, 37);
	}
	
	
	
	function testGetName() {
		$storage = Storage::fromName($this->pdo, "backup-main01");
		$this->assertEquals("backup-main01", $storage->getName());
	}
	
	function testGetId() {
		$storage = Storage::fromName($this->pdo, "backup-main02");
		$this->assertEquals("2", $storage->getId());
	}
	
	function testGetHexArray() {
		$hex = StorageBasic::getHexArray(37177506666152);
		$target = array("00", "00", "21", "d0", "10", "14", "16", "a8");
		$this->assertEquals($target, $hex);
	}
	
	function testGetPathForId() {
		$shared = new Shared();
		$shared->useSQLite(__DIR__."/test.sqlite");
		$pdo = $shared->getEPDO();
		$storage = new StorageBasic($pdo, "backup-main01", __DIR__."/storage/backup-main");
		$target = __DIR__."/storage/backup-main/00/00/21/d0/10/14/16/a8.cp";
		$this->assertEquals($target, $storage->getPathForId(37177506666152));
	}
}
