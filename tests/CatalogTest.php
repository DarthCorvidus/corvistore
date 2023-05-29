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
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$catalog = new Catalog(TestHelper::getEPDO(), $node);
		$this->assertInstanceOf(Catalog::class, $catalog);
	}
	
	function testAddNewDirectory() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$file = new File("/tmp/");
		$catalog = new Catalog(TestHelper::getEPDO(), $node);
		$catalogEntry = $catalog->newEntry($file);
		
		$catTarget[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_parent" => NULL);
		$catDB = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($catTarget, $catDB);
		
		$verTarget[0]["dvs_id"] = 1;
		$verTarget[0]["dvs_atime"] = NULL;
		$verTarget[0]["dvs_ctime"] = NULL;
		$verTarget[0]["dvs_mtime"] = NULL;
		$verTarget[0]["dvs_size"] = NULL;
		$verTarget[0]["dvs_permissions"] = $file->getPerms();
		$verTarget[0]["dvs_owner"] = $file->getOwner();
		$verTarget[0]["dvs_group"] = $file->getGroup();
		$verTarget[0]["dvs_type"] = Catalog::TYPE_DIR;
		$verTarget[0]["dvs_stored"] = 1;
		$verTarget[0]["dc_id"] = 1;
		
		$verDB = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dc_id");
		unset($verDB[0]["dvs_created_local"]);
		unset($verDB[0]["dvs_created_epoch"]);
		$this->assertEquals($verTarget, $verDB);
	}
	/*
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
	*/
	
	function testAddNewParented() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createDir("/Pictures/vacations/2023_thailand");
		$catalog = new Catalog(TestHelper::getEPDO(), $node);
		$catalogEntry = $catalog->newEntry(new File("/tmp/"));
		$catalogEntry = $catalog->newEntry(new File("/tmp/crow-protect"), $catalogEntry);
		#$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect"), $catalogEntry);
		#$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect/Pictures/"), $catalogEntry);
		#$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/"), $catalogEntry);
		#$catalogEntry = $catalog->loadcreateParented(new SourceObject($node, "/tmp/crow-protect/Pictures/vacations/2023_thailand"), $catalogEntry);
		$target[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_parent" => NULL);
		$target[1] = array("dc_id" => 2, "dc_name" => "crow-protect", "dnd_id" => 1, "dc_parent" => 1);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$this->assertEquals($target, $database);
	}

	/*
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
	*/
	
	function testAddNewFile() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createRandom("/beach.bin", 12);
		$catalog = new Catalog(TestHelper::getEPDO(), $node);
		$tmp = $catalog->newEntry(new File("/tmp"));
		$cp = $catalog->newEntry(new File("/tmp/crow-protect"), $tmp);
		$file = new File("/tmp/crow-protect/beach.bin");
		$entry = $catalog->newEntry($file, $cp);
		
		$catTarget[0] = array("dc_id" => 1, "dc_name" => "tmp", "dnd_id" => 1, "dc_parent" => NULL);
		$catTarget[1] = array("dc_id" => 2, "dc_name" => "crow-protect", "dnd_id" => 1, "dc_parent" => 1);
		$catTarget[2] = array("dc_id" => 3, "dc_name" => "beach.bin", "dnd_id" => 1, "dc_parent" => 2);
		$catDB = TestHelper::dumpTable(TestHelper::getEPDO(), "d_catalog", "dc_id");
		$verDB = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");

		$verTarget["dvs_id"] = 3;
		$verTarget["dvs_atime"] = NULL;
		$verTarget["dvs_ctime"] = NULL;
		$verTarget["dvs_mtime"] = $file->getMTime();
		$verTarget["dvs_size"] = 12*1024;
		$verTarget["dvs_permissions"] = $file->getPerms();
		$verTarget["dvs_owner"] = $file->getOwner();
		$verTarget["dvs_group"] = $file->getGroup();
		$verTarget["dvs_type"] = Catalog::TYPE_FILE;
		$verTarget["dvs_stored"] = 0;
		$verTarget["dc_id"] = 3;
		unset($verDB[2]["dvs_created_epoch"]);
		unset($verDB[2]["dvs_created_local"]);
		
		$this->assertEquals($catTarget, $catDB);
		$this->assertEquals($verTarget, $verDB[2]);
	}
}