<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class CatalogEntriesTest extends TestCase {
	private int $serial = 1;
	protected function tearDown(): void {
		$this->serial = 1;
	}
	static function setUpBeforeClass(): void {
		TestHelper::createDatabase();
		TestHelper::createStorage();
		$cpadm = new CPAdm(TestHelper::getEPDO(), array());
		$cpadm->handleCommand(new CommandParser("define storage basic01 type=basic location=".__DIR__."/../storage/basic01/"));
		$cpadm->handleCommand(new CommandParser("define partition backup-main type=common storage=basic01"));
		$cpadm->handleCommand(new CommandParser("define policy forever partition=backup-main"));
		$cpadm->handleCommand(new CommandParser("define node test01 policy=forever password=secret"));
		$cpadm->handleCommand(new CommandParser("define node test02 policy=forever password=secret"));
		$cpadm->handleCommand(new CommandParser("define node test03 policy=forever password=secret"));
	}
	
	static function tearDownAfterClass(): void {
		TestHelper::deleteDatabase();
		TestHelper::deleteStorage();
	}

	private function createBogusCatalogEntry(string $path): \CatalogEntry {
		$array = array();
		$array["dc_id"] = $this->serial++;
		$array["dnd_id"] = Node::fromName(TestHelper::getEPDO(), "test03")->getId();
		$array["dc_dirname"] = dirname($path);
		$array["dc_name"] = basename($path);
		$array["dc_parent"] = NULL;
	return CatalogEntry::fromArray($array);
	}
	
	function testConstruct(): void {
		$ce = new CatalogEntries("/");
		$this->assertInstanceOf(CatalogEntries::class, $ce);
		$this->assertSame(0, $ce->getCount());
		$this->assertSame("/", $ce->getDirname());
	}
	
	function testTest(): void {
		$array = array();
		$array["dc_id"] = 1;
		$array["dnd_id"] = Node::fromName(TestHelper::getEPDO(), "test03")->getId();
		$array["dc_dirname"] = "/";
		$array["dc_name"] = "root";
		$array["dc_parent"] = NULL;
		$manual = CatalogEntry::fromArray($array);
		$generated = $this->createBogusCatalogEntry("/root");
		$this->assertEquals($manual, $generated);
	}
	
	function testAddCatalogEntry(): void {
		$ces = new CatalogEntries("/");
		$ce01 = $this->createBogusCatalogEntry("/root");
		$ces->addEntry($ce01);
		$this->assertSame(1, $ces->getCount());
		$ce02 = $this->createBogusCatalogEntry("/var");
		$ces->addEntry($ce02);
		$this->assertSame(2, $ces->getCount());
		$ce03 = $this->createBogusCatalogEntry("/lib");
		$ces->addEntry($ce03);
		$this->assertSame(3, $ces->getCount());
	}
	
	function testAddDuplicate(): void {
		$ces = new CatalogEntries("/");
		$ce01 = $this->createBogusCatalogEntry("/root");
		$ces->addEntry($ce01);
		$this->expectException(\RuntimeException::class);
		$ces->addEntry($ce01);
	}
	
	function testGetId(): void {
		$ces = new CatalogEntries("/");
		$ce01 = $this->createBogusCatalogEntry("/root");
		$ces->addEntry($ce01);

		$ce02 = $this->createBogusCatalogEntry("/var");
		$ces->addEntry($ce02);

		$ce03 = $this->createBogusCatalogEntry("/lib");
		$ces->addEntry($ce03);
		
		$this->assertSame($ce01, $ces->getEntry(0));
		$this->assertSame($ce02, $ces->getEntry(1));
		$this->assertSame($ce03, $ces->getEntry(2));
	}
	
	function testGetInvalidId(): void {
		$ces = new CatalogEntries("/");
		$ce01 = $this->createBogusCatalogEntry("/root");
		$ces->addEntry($ce01);

		$ce02 = $this->createBogusCatalogEntry("/var");
		$ces->addEntry($ce02);

		$ce03 = $this->createBogusCatalogEntry("/lib");
		$ces->addEntry($ce03);
		
		$this->expectException(\OutOfBoundsException::class);
		$ces->getEntry(3);
	}

	function testHasName(): void {
		$ces = new CatalogEntries("/");
		$ce01 = $this->createBogusCatalogEntry("/root");
		$ces->addEntry($ce01);

		$ce02 = $this->createBogusCatalogEntry("/var");
		$ces->addEntry($ce02);

		$ce03 = $this->createBogusCatalogEntry("/lib");
		$ces->addEntry($ce03);

		$this->assertSame(true, $ces->hasName("var"));
		$this->assertSame(false, $ces->hasName("log"));
	}
	
	function testGetByName(): void {
		$ces = new CatalogEntries("/");
		$ce01 = $this->createBogusCatalogEntry("/root");
		$ces->addEntry($ce01);

		$ce02 = $this->createBogusCatalogEntry("/var");
		$ces->addEntry($ce02);

		$ce03 = $this->createBogusCatalogEntry("/lib");
		$ces->addEntry($ce03);

		$this->assertSame($ce02, $ces->getByName("var"));
	}
	
	function testGetByInvalidName(): void {
		$ces = new CatalogEntries("/");
		$ce01 = $this->createBogusCatalogEntry("/root");
		$ces->addEntry($ce01);

		$ce02 = $this->createBogusCatalogEntry("/var");
		$ces->addEntry($ce02);

		$ce03 = $this->createBogusCatalogEntry("/lib");
		$ces->addEntry($ce03);

		$this->expectException(\InvalidArgumentException::class);
		$this->assertSame($ce02, $ces->getByName("home"));
	}
}