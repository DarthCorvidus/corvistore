<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class CatalogEntryTest extends TestCase {
	function __construct() {
		parent::__construct();
		$this->now = time();
	}
	static function setUpBeforeClass() {
		TestHelper::resetDatabase();
		$cpadm = new CPAdm(TestHelper::getEPDO(), array());
		$cpadm->handleCommand(new CommandParser("define storage basic01 type=basic location=".__DIR__."/../storage/basic01/"));
		$cpadm->handleCommand(new CommandParser("define partition backup-main type=common storage=basic01"));
		$cpadm->handleCommand(new CommandParser("define policy forever partition=backup-main"));
		$cpadm->handleCommand(new CommandParser("define node test01 policy=forever password=secret"));
		$cpadm->handleCommand(new CommandParser("define node test02 policy=forever password=secret"));
		$cpadm->handleCommand(new CommandParser("define node test03 policy=forever password=secret"));
	}

	function testFromArray() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$array["dc_id"] = 1;
		$array["dnd_id"] = Node::fromName(TestHelper::getEPDO(), "test03")->getId();
		$array["dc_name"] = "root";
		$array["dc_parent"] = NULL;
		$ce = CatalogEntry::fromArray(TestHelper::getEPDO(), $array);
		$this->assertInstanceOf(CatalogEntry::class, $ce);
	}
	
	function testFromId() {
		$array["dc_id"] = 1;
		$array["dnd_id"] = Node::fromName(TestHelper::getEPDO(), "test03")->getId();
		$array["dc_name"] = "root";
		$array["dc_parent"] = NULL;
		TestHelper::getEPDO()->create("d_catalog", $array);
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 1);
		$this->assertInstanceOf(CatalogEntry::class, $ce);
	}
	function testGetId() {
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 1);
		$this->assertEquals(1, $ce->getId());
	}
	
	function testGetName() {
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 1);
		$this->assertEquals("root", $ce->getName());
	}
	
	function testHasNoParentId() {
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 1);
		$this->assertEquals(FALSE, $ce->hasParentId());
	}

	function testGetNoParentId() {
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 1);
		$this->expectException(RuntimeException::class);
		$ce->getParentId();
	}
	
	function testHasParentId() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$array["dc_id"] = 2;
		$array["dnd_id"] = Node::fromName(TestHelper::getEPDO(), "test01")->getId();
		$array["dc_name"] = ".bash_rc";
		$array["dc_parent"] = 1;
		TestHelper::getEPDO()->create("d_catalog", $array);
		
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 2);
		$this->assertEquals(TRUE, $ce->hasParentId());
	}
	
	function testGetParentId() {
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 2);
		$this->assertEquals(1, $ce->getParentId());
	}
	
	function testGetNodeId() {
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 1);
		$this->assertEquals(3, $ce->getNodeId());
	}
	
	function testGetVersions() {
		$ce = CatalogEntry::fromId(TestHelper::getEPDO(), 1);
		$this->assertInstanceOf(Versions::class, $ce->getVersions());
	}
	
}