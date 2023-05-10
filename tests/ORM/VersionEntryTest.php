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
		$cpadm = new CPAdm(TestHelper::getEPDO());
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
		$example["dvs_id"] = "25";
		$example["dvs_atime"] = "11021";
		$example["dvs_mtime"] = "11000";
		$example["dvs_ctime"] = "10000";
		$example["dvs_permissions"] = "17407";
		$example["dvs_owner"] = "hm";
		$example["dvs_group"] = "users";
		$example["dvs_size"] = "2186";
		$example["dvs_created"] = "11237";
		$example["dc_id"] = "12";
		$example["dvs_stored"] = "0";
		$entry = VersionEntry::fromArray($example);
		$this->assertInstanceOf(VersionEntry::class, $entry);
		$this->assertEquals(25, $entry->getId());
		$this->assertEquals(11021, $entry->getATime());
		$this->assertEquals(11000, $entry->getMTime());
		$this->assertEquals(10000, $entry->getCTime());
		$this->assertEquals(17407, $entry->getPermissions());
		$this->assertEquals("hm", $entry->getOwner());
		$this->assertEquals("users", $entry->getGroup());
		$this->assertEquals(2186, $entry->getSize());
		$this->assertEquals(11237, $entry->getCreated());
		$this->assertEquals(12, $entry->getCatalogId());
	}
	
	function testFromId() {
		$example["dvs_id"] = "25";
		$example["dvs_atime"] = "11021";
		$example["dvs_mtime"] = "11000";
		$example["dvs_ctime"] = "10000";
		$example["dvs_permissions"] = "17407";
		$example["dvs_owner"] = "hm";
		$example["dvs_group"] = "users";
		$example["dvs_size"] = "2186";
		$example["dvs_created"] = "11237";
		$example["dc_id"] = "12";
		$id = TestHelper::getEPDO()->create("d_version", $example);
		$this->assertInstanceOf(VersionEntry::class, VersionEntry::fromId(TestHelper::getEPDO(), 25));
	}
	
	function testFromNoValidId() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("No version with id '27' found");
		VersionEntry::fromId(TestHelper::getEPDO(), 27);
	}
}