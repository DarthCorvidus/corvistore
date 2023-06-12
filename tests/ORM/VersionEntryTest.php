<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class VersionEntryTest extends TestCase {
	function __construct() {
		parent::__construct();
		$this->now = mktime();
	}
	static function setUpBeforeClass() {
		TestHelper::resetDatabase();
		$cpadm = new CPAdm(TestHelper::getEPDO(), array());
		$cpadm->handleCommand(new CommandParser("define storage basic01 type=basic location=".__DIR__."/../storage/basic01/"));
		$cpadm->handleCommand(new CommandParser("define partition backup-main type=common storage=basic01"));
		$cpadm->handleCommand(new CommandParser("define policy forever partition=backup-main"));
		$cpadm->handleCommand(new CommandParser("define node test01 policy=forever"));
		$cpadm->handleCommand(new CommandParser("define node test02 policy=forever"));
		$cpadm->handleCommand(new CommandParser("define node test03 policy=forever"));
		$mockup = new MockupFiles("/tmp/crow-protect");
		$mockup->clear();
		$mockup->createRandom("/image.bin", 10);
	}
	
	function testFromArray() {
		$time = mktime();
		$datetime = date("Y-m-d H:i:sP", $time);
		$example["dvs_id"] = "25";
		$example["dvs_type"] = Catalog::TYPE_FILE;
		#$example["dvs_atime"] = "11021";
		$example["dvs_mtime"] = "11000";
		#$example["dvs_ctime"] = "10000";
		$example["dvs_permissions"] = "17407";
		$example["dvs_owner"] = "hm";
		$example["dvs_group"] = "users";
		$example["dvs_size"] = "2186";
		$example["dvs_created_epoch"] = $time;
		$example["dvs_created_local"] = $datetime;
		$example["dc_id"] = "12";
		$example["dvs_stored"] = "0";
		$entry = VersionEntry::fromArray($example);
		$this->assertInstanceOf(VersionEntry::class, $entry);
		$this->assertEquals(25, $entry->getId());
		#$this->assertEquals(11021, $entry->getATime());
		$this->assertEquals(11000, $entry->getMTime());
		#$this->assertEquals(10000, $entry->getCTime());
		$this->assertEquals(17407, $entry->getPermissions());
		$this->assertEquals("hm", $entry->getOwner());
		$this->assertEquals("users", $entry->getGroup());
		$this->assertEquals(2186, $entry->getSize());
		$this->assertEquals($time, $entry->getCreated());
		$this->assertEquals(12, $entry->getCatalogId());
	}
	
	function testFromId() {
		$time = mktime();
		$datetime = date("Y-m-d H:i:sP", $time);
		$example["dvs_id"] = "25";
		$example["dvs_atime"] = "11021";
		$example["dvs_mtime"] = "11000";
		$example["dvs_ctime"] = "10000";
		$example["dvs_permissions"] = "17407";
		$example["dvs_owner"] = "hm";
		$example["dvs_group"] = "users";
		$example["dvs_size"] = "2186";
		$example["dvs_created_epoch"] = $time;
		$example["dvs_created_local"] = $datetime;
		$example["dc_id"] = "12";
		$id = TestHelper::getEPDO()->create("d_version", $example);
		$this->assertInstanceOf(VersionEntry::class, VersionEntry::fromId(TestHelper::getEPDO(), 25));
	}
	
	function testToBinary() {
		$time = mktime();
		$datetime = date("Y-m-d H:i:sP", $time);
		$example["dvs_id"] = "25";
		$example["dvs_type"] = Catalog::TYPE_FILE;
		$example["dvs_atime"] = "11021";
		$example["dvs_mtime"] = "11000";
		$example["dvs_ctime"] = "10000";
		$example["dvs_permissions"] = "17407";
		$example["dvs_owner"] = "hm";
		$example["dvs_group"] = "users";
		$example["dvs_size"] = "2186";
		$example["dvs_created_epoch"] = $time;
		$example["dvs_created_local"] = $datetime;
		$example["dvs_stored"] = 1;
		$example["dc_id"] = "12";
		$obj = VersionEntry::fromArray($example);
		$binary = $obj->toBinary();
		$this->assertEquals(chr(25).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0), substr($binary, 0, 8));
		$this->assertEquals(chr(248).chr(42).chr(0).chr(0), substr($binary, 8, 4));
	}
	
	function testFromBinary() {
		$time = mktime();
		$datetime = date("Y-m-d H:i:sP", $time);
		$example["dvs_id"] = "25";
		$example["dvs_type"] = Catalog::TYPE_FILE;
		$example["dvs_atime"] = "11021";
		$example["dvs_mtime"] = "11000";
		$example["dvs_ctime"] = "10000";
		$example["dvs_permissions"] = "17407";
		$example["dvs_owner"] = "hm";
		$example["dvs_group"] = "users";
		$example["dvs_size"] = "2186";
		$example["dvs_created_epoch"] = $time;
		$example["dvs_created_local"] = $datetime;
		$example["dvs_stored"] = 1;
		$example["dc_id"] = "12";
		$obj = VersionEntry::fromArray($example);
		$binary = $obj->toBinary();
		$new = VersionEntry::fromBinary($binary);
		$this->assertEquals($obj, $new);
	}
	
	function testFromNoValidId() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("No version with id '27' found");
		VersionEntry::fromId(TestHelper::getEPDO(), 27);
	}
}