<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class VersionsTest extends TestCase {
	private $mockup;
	function __construct() {
		parent::__construct();
		$this->now = mktime();
		$this->mockup = new MockupFiles("/tmp/crow-protect/");
	}

	function setUp() {
		TestHelper::resetDatabase();
		$this->mockup->clear();
		TestHelper::initServer();
	}
	
	function testConstruct() {
		$version = new Versions();
		$this->assertInstanceOf(Versions::class, $version);
	}
	/**
	 * Test to add a version, but not store it (mark it as stored)
	 */
	function testAddVersion() {
		$time = mktime();
		$array = array();
		$array["dvs_id"] = 27;
		$array["dvs_type"] = Catalog::TYPE_DELETED;
		$array["dvs_created_epoch"] = $time;
		$array["dvs_created_date"] = date("Y-m-d H:i:sP", $time);
		$array["dc_id"] = 12;
		
		$version = VersionEntry::fromArray($array);
		
		$versions = new Versions();
		$this->assertEquals(NULL, $versions->addVersion($version));
	}
	
	function testGetCount() {
		$versions = new Versions();
		
		$time = mktime();
		$dir = array();
		$dir["dvs_id"] = 27;
		$dir["dvs_type"] = Catalog::TYPE_DIR;
		$dir["dvs_created_epoch"] = $time;
		$dir["dvs_created_date"] = date("Y-m-d H:i:sP", $time);
		$dir["dvs_permissions"] = fileperms(__DIR__);
		$dir["dvs_owner"] = "joedoe";
		$dir["dvs_group"] = "joedoe";
		$dir["dvs_stored"] = 1;
		$dir["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($dir));

		$file = array();
		$file["dvs_id"] = 28;
		$file["dvs_type"] = Catalog::TYPE_FILE;
		$file["dvs_created_epoch"] = $time+1;
		$file["dvs_created_date"] = date("Y-m-d H:i:sP", $time+1);
		$file["dvs_permissions"] = fileperms(__DIR__);
		$file["dvs_owner"] = "joedoe";
		$file["dvs_group"] = "joedoe";
		$file["dvs_stored"] = 1;
		$file["dvs_mtime"] = $time-100;
		$file["dvs_size"] = 4096;
		$file["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($file));
		
		
		$delete = array();
		$delete["dvs_id"] = 29;
		$delete["dvs_type"] = Catalog::TYPE_DELETED;
		$delete["dvs_created_epoch"] = $time+2;
		$delete["dvs_created_date"] = date("Y-m-d H:i:sP", $time+2);
		$delete["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($delete));
		$this->assertEquals(3, $versions->getCount());
	}

	function testGetEntry() {
		$versions = new Versions();
		
		$time = mktime();
		$dir = array();
		$dir["dvs_id"] = 27;
		$dir["dvs_type"] = Catalog::TYPE_DIR;
		$dir["dvs_created_epoch"] = $time;
		$dir["dvs_created_date"] = date("Y-m-d H:i:sP", $time);
		$dir["dvs_permissions"] = fileperms(__DIR__);
		$dir["dvs_owner"] = "joedoe";
		$dir["dvs_group"] = "joedoe";
		$dir["dvs_stored"] = 1;
		$dir["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($dir));

		$file = array();
		$file["dvs_id"] = 28;
		$file["dvs_type"] = Catalog::TYPE_FILE;
		$file["dvs_created_epoch"] = $time+1;
		$file["dvs_created_date"] = date("Y-m-d H:i:sP", $time+1);
		$file["dvs_permissions"] = fileperms(__DIR__);
		$file["dvs_owner"] = "joedoe";
		$file["dvs_group"] = "joedoe";
		$file["dvs_stored"] = 1;
		$file["dvs_mtime"] = $time-100;
		$file["dvs_size"] = 4096;
		$file["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($file));
		
		
		$delete = array();
		$delete["dvs_id"] = 29;
		$delete["dvs_type"] = Catalog::TYPE_DELETED;
		$delete["dvs_created_epoch"] = $time+2;
		$delete["dvs_created_date"] = date("Y-m-d H:i:sP", $time+2);
		$delete["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($delete));
		$this->assertEquals(Catalog::TYPE_FILE, $versions->getVersion(1)->getType());
	}
	
	function testGetLatest() {
		$versions = new Versions();
		
		$time = mktime();
		$dir = array();
		$dir["dvs_id"] = 27;
		$dir["dvs_type"] = Catalog::TYPE_DIR;
		$dir["dvs_created_epoch"] = $time;
		$dir["dvs_created_date"] = date("Y-m-d H:i:sP", $time);
		$dir["dvs_permissions"] = fileperms(__DIR__);
		$dir["dvs_owner"] = "joedoe";
		$dir["dvs_group"] = "joedoe";
		$dir["dvs_stored"] = 1;
		$dir["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($dir));

		$file = array();
		$file["dvs_id"] = 28;
		$file["dvs_type"] = Catalog::TYPE_FILE;
		$file["dvs_created_epoch"] = $time+1;
		$file["dvs_created_date"] = date("Y-m-d H:i:sP", $time+1);
		$file["dvs_permissions"] = fileperms(__DIR__);
		$file["dvs_owner"] = "joedoe";
		$file["dvs_group"] = "joedoe";
		$file["dvs_stored"] = 1;
		$file["dvs_mtime"] = $time-100;
		$file["dvs_size"] = 4096;
		$file["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($file));
		
		$delete = array();
		$delete["dvs_id"] = 29;
		$delete["dvs_type"] = Catalog::TYPE_DELETED;
		$delete["dvs_created_epoch"] = $time+2;
		$delete["dvs_created_date"] = date("Y-m-d H:i:sP", $time+2);
		$delete["dc_id"] = 12;
		$versions->addVersion(VersionEntry::fromArray($delete));
		$this->assertEquals(Catalog::TYPE_DELETED, $versions->getLatest()->getType());
	}

}