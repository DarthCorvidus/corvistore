<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class PartitionTest extends TestCase {
	function __construct() {
		parent::__construct();
		$this->now = mktime();
	}
	static function setUpBeforeClass() {
		TestHelper::resetDatabase();
	}
	
	function testDefine() {
		$command = new CommandParser("define storage primary type=basic location=".__DIR__."/storage/basic01/");
		Storage::define(TestHelper::getEPDO(), $command);

		$command = new CommandParser("define storage secondary type=basic location=".__DIR__."/storage/basic02/");
		Storage::define(TestHelper::getEPDO(), $command);
		
		//"wrong" order on purpose
		$command = new CommandParser("define partition backup-secondary storage=secondary type=common");
		Partition::define(TestHelper::getEPDO(), $command);

		$command = new CommandParser("define partition backup-primary storage=primary type=common");
		Partition::define(TestHelper::getEPDO(), $command);
		
		$target[0] = array("dpt_id" => 1, "dst_id" => 2, "dpt_name"=>"backup-secondary", "dpt_type"=>"common", "dpt_copy" => NULL, "dpt_nextpt" => NULL);
		$target[1] = array("dpt_id" => 2, "dst_id" => 1, "dpt_name"=>"backup-primary", "dpt_type"=>"common", "dpt_copy" => NULL, "dpt_nextpt" => NULL);
		$this->assertEquals($target, TestHelper::dumpTable(TestHelper::getEPDO(), "d_partition", "dpt_id"));
	}
	
	function testDefineUnique() {
		$command = new CommandParser("define partition backup-primary storage=primary type=common");
		$this->expectException(Exception::class);
		Partition::define(TestHelper::getEPDO(), $command);
	}
	
	function testFromArray() {
		$array = TestHelper::getEPDO()->row("select * from d_partition where dpt_id = ?", array(1));
		$partition = Partition::fromArray(TestHelper::getEPDO(), $array);
		$this->assertInstanceOf(Partition::class, $partition);
	}
	
	function testFromName() {
		$partition = Partition::fromName(TestHelper::getEPDO(), "backup-primary");
		$this->assertInstanceOf(Partition::class, $partition);
		$this->assertEquals("backup-primary", $partition->getName());
		$this->assertEquals(1, $partition->getStorageId());
		$this->assertEquals("common", $partition->getType());
	}
	
	function testFromNameBogus() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Partition 'squid' does not exist.");
		$partition = Partition::fromName(TestHelper::getEPDO(), "squid");
	}
	
	function testFromId() {
		$partition = Partition::fromId(TestHelper::getEPDO(), 2);
		$this->assertInstanceOf(Partition::class, $partition);
		$this->assertEquals("backup-primary", $partition->getName());
		$this->assertEquals(1, $partition->getStorageId());
		$this->assertEquals("common", $partition->getType());
	}
	
	function testFromIdBogus() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Partition with id '25' does not exist.");
		$partition = Partition::fromId(TestHelper::getEPDO(), 25);
	}
	
}
