<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class CatalogTest extends TestCase {
	private $mockup;
	function __construct() {
		parent::__construct();
		$this->now = mktime();
		$this->mockup = new MockupFiles("/tmp/crow-protect/");
	}
	static function setUpBeforeClass() {
		#TestHelper::resetDatabase();
		#$cpadm = new CPAdm(TestHelper::getEPDO());
		#$cpadm->handleCommand(new CommandParser("define storage basic01 type=basic location=".__DIR__."/storage/basic01/"));
		#$cpadm->handleCommand(new CommandParser("define partition backup-main type=common storage=basic01"));
		#$cpadm->handleCommand(new CommandParser("define policy forever partition=backup-main"));
		#$cpadm->handleCommand(new CommandParser("define node test01 policy=forever"));
	}
	
	function setUp() {
		TestHelper::resetDatabase();
		$this->mockup->clear();
		TestHelper::initServer();
	}
	
	function testConstruct() {
		$catalog = new Catalog(TestHelper::getEPDO());
		$this->assertInstanceOf(Catalog::class, $catalog);
	}
	
	function testCreateDirectory() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$source = new SourceObject($node, "/tmp/");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = $catalog->loadcreate($source);
		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}

	function testCreateDirectoryUnique() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$source = new SourceObject($node, "/tmp/");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalog->loadcreate($source);
		$catalog->loadcreate($source);
		$catalog->loadcreate($source);
		
		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}

	function testCreateDirAllTheWayUp() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createDir("/Pictures/vacations/2023_thailand");
		$source = new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/2023_thailand");
		
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = $catalog->loadcreate($source);
		
		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$target[1] = array("dc_id" => 2, "dc_name" => "crow-protect", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 1);
		$target[2] = array("dc_id" => 3, "dc_name" => "Pictures", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 2);
		$target[3] = array("dc_id" => 4, "dc_name" => "vacations", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 3);
		$target[4] = array("dc_id" => 5, "dc_name" => "2023_thailand", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 4);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}

	function testCreateAllTheWayUpUnique() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createDir("/Pictures/vacations/2023_thailand");
		$source = new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/2023_thailand");
		
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = $catalog->loadcreate($source);
		$catalogEntry = $catalog->loadcreate($source);
		$catalogEntry = $catalog->loadcreate($source);
		
		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$target[1] = array("dc_id" => 2, "dc_name" => "crow-protect", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 1);
		$target[2] = array("dc_id" => 3, "dc_name" => "Pictures", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 2);
		$target[3] = array("dc_id" => 4, "dc_name" => "vacations", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 3);
		$target[4] = array("dc_id" => 5, "dc_name" => "2023_thailand", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 4);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}
	
	function testCreateParented() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createDir("/Pictures/vacations/2023_thailand");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = $catalog->loadcreate(new SourceObject($node, "/tmp/"));
		$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect"), $catalogEntry);
		$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect/Pictures/"), $catalogEntry);
		$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/"), $catalogEntry);
		$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/2023_thailand"), $catalogEntry);

		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$target[1] = array("dc_id" => 2, "dc_name" => "crow-protect", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 1);
		$target[2] = array("dc_id" => 3, "dc_name" => "Pictures", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 2);
		$target[3] = array("dc_id" => 4, "dc_name" => "vacations", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 3);
		$target[4] = array("dc_id" => 5, "dc_name" => "2023_thailand", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 4);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}

	function testCreateParentedUnique() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createDir("/Pictures/vacations/2023_thailand");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = $catalog->loadcreate(new SourceObject($node, "/tmp/"));
		$catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect"), $catalogEntry);
		$catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect"), $catalogEntry);
		$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect"), $catalogEntry);
		$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect/Pictures"), $catalogEntry);

		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$target[1] = array("dc_id" => 2, "dc_name" => "crow-protect", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 1);
		$target[2] = array("dc_id" => 3, "dc_name" => "Pictures", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 2);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}
	
	function testCreateFiles() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createDir("/Pictures/vacations/2023_thailand/");
		$this->mockup->createRandom("/Pictures/vacations/2023_thailand/beach.bin", 12);
		$this->mockup->createRandom("/Pictures/vacations/2023_thailand/jungle.bin", 12);
		$this->mockup->createRandom("/Pictures/vacations/2023_thailand/temple.bin", 12);
		
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalog->loadcreate(new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/2023_thailand/beach.bin"));
		$catalog->loadcreate(new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/2023_thailand/jungle.bin"));
		$catalog->loadcreate(new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/2023_thailand/temple.bin"));
		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$target[1] = array("dc_id" => 2, "dc_name" => "crow-protect", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 1);
		$target[2] = array("dc_id" => 3, "dc_name" => "Pictures", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 2);
		$target[3] = array("dc_id" => 4, "dc_name" => "vacations", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 3);
		$target[4] = array("dc_id" => 5, "dc_name" => "2023_thailand", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 4);
		$target[5] = array("dc_id" => 6, "dc_name" => "beach.bin", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_FILE, "dc_parent" => 5);
		$target[6] = array("dc_id" => 7, "dc_name" => "jungle.bin", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_FILE, "dc_parent" => 5);
		$target[7] = array("dc_id" => 8, "dc_name" => "temple.bin", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_FILE, "dc_parent" => 5);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}
	/**
	 * A file can exist as a directory and a file in the catalog, when you first
	 * create a file and then remove it and create a directory by the same name.
	 * 
	 */
	function testCreateFileAndDir() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createRandom("/Pictures", 1);
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalog->loadcreate(new SourceObject($node, "/tmp/crow-protect/Pictures"));
		$this->mockup->clear();
		$this->mockup->createDir("/Pictures/vacation");
		$catalog->loadcreate(new SourceObject($node, "/tmp/crow-protect/Pictures/vacation"));
		
		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$target[1] = array("dc_id" => 2, "dc_name" => "crow-protect", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 1);
		$target[2] = array("dc_id" => 3, "dc_name" => "Pictures", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_FILE, "dc_parent" => 2);
		$target[3] = array("dc_id" => 4, "dc_name" => "Pictures", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 2);
		$target[4] = array("dc_id" => 5, "dc_name" => "vacation", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => 4);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}
	
	function testPrivateCreate() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createRandom("/Pictures", 1);
		$catalog = new Catalog(TestHelper::getEPDO());
		$source = new SourceObject($node, "/tmp/");
		
		TestHelper::invoke($catalog, "create", array($source));
		
		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_type" => CatalogEntry::TYPE_DIR, "dc_parent" => NULL);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}
}