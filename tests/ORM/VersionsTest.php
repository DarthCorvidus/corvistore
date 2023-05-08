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
		$this->mockup->delete();
		TestHelper::initServer();
	}
	
	function testConstruct() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$source = new SourceObject($node, "/tmp/");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = TestHelper::invoke($catalog, "create", array($source));
		$version = new Versions(TestHelper::getEPDO(), $catalogEntry, $source);
			
		$this->assertInstanceOf(Versions::class, $version);
	}
	/**
	 * Test to add a version, but not store it (mark it as stored)
	 */
	function testAddVersion() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$source = new SourceObject($node, "/tmp/");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = TestHelper::invoke($catalog, "create", array($source));
		
		$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		$versionEntry = $versions->addVersion($source);
		
		$target[0] = array("dvs_id" => "1", "dvs_atime" => fileatime("/tmp"), "dvs_mtime" => filemtime("/tmp/"), "dvs_ctime"=> filectime("/tmp/"), "dvs_permissions" => fileperms("/tmp/"), "dvs_owner" => TestHelper::fileowner("/tmp/"), "dvs_group" => TestHelper::filegroup("/tmp/"), "dvs_created" => $versionEntry->getCreated(), "dvs_size"=> filesize("/tmp/"), "dc_id"=>1, "dvs_stored"=>"0", "dvs_deleted"=>"0");
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
		$this->assertEquals($target, $database);
	}

	/**
	 * Add version and mark it as stored.
	 */
	function testAddVersionStored() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$source = new SourceObject($node, "/tmp/");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = TestHelper::invoke($catalog, "create", array($source));
		
		$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		$versionEntry = $versions->addVersion($source);
		$versions->setStored($versionEntry);
		
		$target[0] = array("dvs_id" => "1", "dvs_atime" => fileatime("/tmp"), "dvs_mtime" => filemtime("/tmp/"), "dvs_ctime"=> filectime("/tmp/"), "dvs_permissions" => fileperms("/tmp/"), "dvs_owner" => TestHelper::fileowner("/tmp/"), "dvs_group" => TestHelper::filegroup("/tmp/"), "dvs_created" => $versionEntry->getCreated(), "dvs_size"=> filesize("/tmp/"), "dc_id"=>1, "dvs_stored"=>"1", "dvs_deleted"=>"0");
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
		$this->assertEquals($target, $database);
	}
	
	/**
	 * Add a version, mark it as stored, and add the same version again. Now
	 * only one entry is expected, as the existing entry is returned instead
	 * of a new one being created.
	 */
	function testAddUnique() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$source = new SourceObject($node, "/tmp/");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = TestHelper::invoke($catalog, "create", array($source));
		
		$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		$versionEntry = $versions->addVersion($source);
		$versions->setStored($versionEntry);
		
		$versionEntry = $versions->addVersion($source);
		
		$target[0] = array("dvs_id" => "1", "dvs_atime" => fileatime("/tmp"), "dvs_mtime" => filemtime("/tmp/"), "dvs_ctime"=> filectime("/tmp/"), "dvs_permissions" => fileperms("/tmp/"), "dvs_owner" => TestHelper::fileowner("/tmp/"), "dvs_group" => TestHelper::filegroup("/tmp/"), "dvs_created" => $versionEntry->getCreated(), "dvs_size"=> filesize("/tmp/"), "dc_id"=>1, "dvs_stored"=>"1", "dvs_deleted"=>"0");
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
		$this->assertEquals($target, $database);
	}
	
	/**
	 * If a value is added but not stored, adding the same value will result
	 * in a new entry, since unstored values are ignored.
	 */
	function testAddSameUnstored() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$source = new SourceObject($node, "/tmp/");
		$catalog = new Catalog(TestHelper::getEPDO());
		$catalogEntry = TestHelper::invoke($catalog, "create", array($source));
		
		$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		$versionEntry = $versions->addVersion($source);
		
		$versionEntry = $versions->addVersion($source);
		$versions->setStored($versionEntry);
		
		$target[0] = array("dvs_id" => "1", "dvs_atime" => fileatime("/tmp"), "dvs_mtime" => filemtime("/tmp/"), "dvs_ctime"=> filectime("/tmp/"), "dvs_permissions" => fileperms("/tmp/"), "dvs_owner" => TestHelper::fileowner("/tmp/"), "dvs_group" => TestHelper::filegroup("/tmp/"), "dvs_created" => $versionEntry->getCreated(), "dvs_size"=> filesize("/tmp/"), "dc_id"=>1, "dvs_stored"=>"0", "dvs_deleted"=>"0");
		$target[1] = array("dvs_id" => "2", "dvs_atime" => fileatime("/tmp"), "dvs_mtime" => filemtime("/tmp/"), "dvs_ctime"=> filectime("/tmp/"), "dvs_permissions" => fileperms("/tmp/"), "dvs_owner" => TestHelper::fileowner("/tmp/"), "dvs_group" => TestHelper::filegroup("/tmp/"), "dvs_created" => $versionEntry->getCreated(), "dvs_size"=> filesize("/tmp/"), "dc_id"=>1, "dvs_stored"=>"1", "dvs_deleted"=>"0");
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
		$this->assertEquals($target, $database);
	}
	
	/**
	 * Add a version, create a new file and add another version. Two records
	 * are expected to exist.
	 */
	function testAddVersionChanged() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createRandom("random.bin", 10);
		$catalog = new Catalog(TestHelper::getEPDO());
		$sourceOld = new SourceObject($node, "/tmp/crow-protect/random.bin");
		$catalogEntry = TestHelper::invoke($catalog, "create", array($sourceOld));
		
		$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		// Add first entry.
		
		$versionEntryFirst = $versions->addVersion($sourceOld);
		$versions->setStored($versionEntryFirst);
		// Replace
		$target[0] = array("dvs_id" => "1", "dvs_atime" => fileatime("/tmp/crow-protect/random.bin"), "dvs_mtime" => filemtime("/tmp/crow-protect/random.bin"), "dvs_ctime"=> filectime("/tmp/crow-protect/random.bin"), "dvs_permissions" => fileperms("/tmp/crow-protect/random.bin"), "dvs_owner" => TestHelper::fileowner("/tmp/crow-protect/random.bin"), "dvs_group" => TestHelper::filegroup("/tmp/crow-protect/random.bin"), "dvs_created" => $versionEntryFirst->getCreated(), "dvs_size"=> filesize("/tmp/crow-protect/random.bin"), "dc_id"=>1, "dvs_stored"=>"1", "dvs_deleted"=>"0");
		sleep(2);
		
		$this->mockup->createRandom("random.bin", 10);
		clearstatcache();
		$sourceNew = new SourceObject($node, "/tmp/crow-protect/random.bin");
		$versionEntrySecond = $versions->addVersion($sourceNew);
		$versions->setStored($versionEntrySecond);
		$target[1] = array("dvs_id" => "2", "dvs_atime" => fileatime("/tmp/crow-protect/random.bin"), "dvs_mtime" => filemtime("/tmp/crow-protect/random.bin"), "dvs_ctime"=> filectime("/tmp/crow-protect/random.bin"), "dvs_permissions" => fileperms("/tmp/crow-protect/random.bin"), "dvs_owner" => TestHelper::fileowner("/tmp/crow-protect/random.bin"), "dvs_group" => TestHelper::filegroup("/tmp/crow-protect/random.bin"), "dvs_created" => $versionEntrySecond->getCreated(), "dvs_size"=> filesize("/tmp/crow-protect/random.bin"), "dc_id"=>1, "dvs_stored"=>"1", "dvs_deleted"=>"0");
		
		#$target[2] = array("dvs_id" => "1", "dvs_atime" => fileatime("/tmp"), "dvs_mtime" => filemtime("/tmp/"), "dvs_ctime"=> filectime("/tmp/"), "dvs_permissions" => fileperms("/tmp/"), "dvs_owner" => TestHelper::fileowner("/tmp/"), "dvs_group" => TestHelper::filegroup("/tmp/"), "dvs_created" => $versionEntry->getCreated(), "dvs_size"=> filesize("/tmp/"), "dc_id"=>1);
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
		$this->assertEquals($target, $database);
	}

	function testAddVersionRestored() {
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$this->mockup->createRandom("random.bin", 10);
		$catalog = new Catalog(TestHelper::getEPDO());
		$sourceOld = new SourceObject($node, "/tmp/crow-protect/random.bin");
		$catalogEntry = TestHelper::invoke($catalog, "create", array($sourceOld));
		
		$versions = new Versions(TestHelper::getEPDO(), $catalogEntry);
		// Add first entry.
		$versionEntryFirst = $versions->addVersion($sourceOld);
		$versions->setStored($versionEntryFirst);
		$target[0] = array("dvs_id" => "1", "dvs_atime" => fileatime("/tmp/crow-protect/random.bin"), "dvs_mtime" => filemtime("/tmp/crow-protect/random.bin"), "dvs_ctime"=> filectime("/tmp/crow-protect/random.bin"), "dvs_permissions" => fileperms("/tmp/crow-protect/random.bin"), "dvs_owner" => TestHelper::fileowner("/tmp/crow-protect/random.bin"), "dvs_group" => TestHelper::filegroup("/tmp/crow-protect/random.bin"), "dvs_created" => $versionEntryFirst->getCreated(), "dvs_size"=> filesize("/tmp/crow-protect/random.bin"), "dc_id"=>1, "dvs_stored"=>"1", "dvs_deleted"=>"0");
		# Move first entry out of the way.
		rename("/tmp/crow-protect/random.bin", "/tmp/crow-protect/random.backup");
		sleep(2);

		//Create second, different entry
		$this->mockup->createRandom("random.bin", 10);
		clearstatcache();
		$sourceNew = new SourceObject($node, "/tmp/crow-protect/random.bin");
		$versionEntrySecond = $versions->addVersion($sourceNew);
		$versions->setStored($versionEntrySecond);
		$target[1] = array("dvs_id" => "2", "dvs_atime" => fileatime("/tmp/crow-protect/random.bin"), "dvs_mtime" => filemtime("/tmp/crow-protect/random.bin"), "dvs_ctime"=> filectime("/tmp/crow-protect/random.bin"), "dvs_permissions" => fileperms("/tmp/crow-protect/random.bin"), "dvs_owner" => TestHelper::fileowner("/tmp/crow-protect/random.bin"), "dvs_group" => TestHelper::filegroup("/tmp/crow-protect/random.bin"), "dvs_created" => $versionEntrySecond->getCreated(), "dvs_size"=> filesize("/tmp/crow-protect/random.bin"), "dc_id"=>1, "dvs_stored"=>"1", "dvs_deleted"=>"0");
		unlink("/tmp/crow-protect/random.bin");
		rename("/tmp/crow-protect/random.backup", "/tmp/crow-protect/random.bin");
		clearstatcache();
		$sourceRestored = new SourceObject($node, "/tmp/crow-protect/random.bin");
		$versionEntryThird = $versions->addVersion($sourceRestored);
		$versions->setStored($versionEntryThird);
		
		$target[2] = array("dvs_id" => "3", "dvs_atime" => fileatime("/tmp/crow-protect/random.bin"), "dvs_mtime" => filemtime("/tmp/crow-protect/random.bin"), "dvs_ctime"=> filectime("/tmp/crow-protect/random.bin"), "dvs_permissions" => fileperms("/tmp/crow-protect/random.bin"), "dvs_owner" => TestHelper::fileowner("/tmp/crow-protect/random.bin"), "dvs_group" => TestHelper::filegroup("/tmp/crow-protect/random.bin"), "dvs_created" => $versionEntryThird->getCreated(), "dvs_size"=> filesize("/tmp/crow-protect/random.bin"), "dc_id"=>1, "dvs_stored"=>"1", "dvs_deleted"=>"0");
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_version", "dvs_id");
		$this->assertEquals($target, $database);
	}
}