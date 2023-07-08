<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class PolicyTest extends TestCase {
	static function setUpBeforeClass() {
		TestHelper::createDatabase();
		TestHelper::createStorage();
	}

	static function tearDownAfterClass() {
		TestHelper::deleteDatabase();
		TestHelper::deleteStorage();
	}

	function testDefine() {
		Storage::define(TestHelper::getEPDO(), new CommandParser("define storage basic01 type=basic location=".__DIR__."/storage/basic01"));
		Storage::define(TestHelper::getEPDO(), new CommandParser("define storage basic02 type=basic location=".__DIR__."/storage/basic01"));
		Partition::define(TestHelper::getEPDO(), new CommandParser("define partition backup-main01 type=common storage=basic01"));
		Partition::define(TestHelper::getEPDO(), new CommandParser("define partition backup-main02 type=common storage=basic02"));
				
		$command = new CommandParser("define policy partition=backup-main02 forever");
		Policy::define(TestHelper::getEPDO(), $command);

		$command = new CommandParser("define policy month partition=backup-main01 retexists=31 retdeleted=15");
		Policy::define(TestHelper::getEPDO(), $command);

		$command = new CommandParser("define policy keepv10d5 partition=backup-main01 verexists=10 verdeleted=5");
		Policy::define(TestHelper::getEPDO(), $command);

		$command = new CommandParser("define policy keepv10d5month partition=backup-main02 verexists=10 verdeleted=5 retexists=31 retdeleted=15");
		Policy::define(TestHelper::getEPDO(), $command);
		
		
		$target[0] = array("dpo_id" => "1", "dpo_name"=>"forever", "dpo_version_exists" => "0", "dpo_version_deleted"=>"0", "dpo_retention_exists" => "0", "dpo_retention_deleted"=>"0", "dpt_id"=>"2");
		$target[1] = array("dpo_id" => "2", "dpo_name"=>"month", "dpo_version_exists" => "0", "dpo_version_deleted"=>"0", "dpo_retention_exists" => "31", "dpo_retention_deleted"=>"15", "dpt_id"=>"1");
		$target[2] = array("dpo_id" => "3", "dpo_name"=>"keepv10d5", "dpo_version_exists" => "10", "dpo_version_deleted"=>"5", "dpo_retention_exists" => "0", "dpo_retention_deleted"=>"0", "dpt_id"=>"1");
		$target[3] = array("dpo_id" => "4", "dpo_name"=>"keepv10d5month", "dpo_version_exists" => "10", "dpo_version_deleted"=>"5", "dpo_retention_exists" => "31", "dpo_retention_deleted"=>"15", "dpt_id"=>"2");
		$this->assertEquals($target, TestHelper::dumpTable(TestHelper::getEPDO(), "d_policy", "dpo_id"));
	}
	
	function testDefineUnique() {
		$command = new CommandParser("define policy forever");
		$this->expectException(Exception::class);
		Policy::define(TestHelper::getEPDO(), $command);
	}

	function testFromArray() {
		$array = TestHelper::getEPDO()->row("select * from d_policy where dpo_id = ?", array(4));
		$policy = Policy::fromArray(TestHelper::getEPDO(), $array);
		$this->assertInstanceOf(Policy::class, $policy);
		$this->assertEquals("4", $policy->getId());
		$this->assertEquals("keepv10d5month", $policy->getName());
		$this->assertEquals("2", $policy->getPartition()->getId());
		$this->assertEquals(10, $policy->getVersionExists());
		$this->assertEquals(5, $policy->getVersionDeleted());
		$this->assertEquals(31, $policy->getRetentionExists());
		$this->assertEquals(15, $policy->getRetentionDeleted());
		
				
	}
	
	function testFromName() {
		$policy = Policy::fromName(TestHelper::getEPDO(), "keepv10d5month");
		$this->assertInstanceOf(Policy::class, $policy);
		$this->assertEquals(4, $policy->getId());
		$this->assertEquals("keepv10d5month", $policy->getName());
		$this->assertEquals("2", $policy->getPartition()->getId());
		$this->assertEquals(10, $policy->getVersionExists());
		$this->assertEquals(5, $policy->getVersionDeleted());
		$this->assertEquals(31, $policy->getRetentionExists());
		$this->assertEquals(15, $policy->getRetentionDeleted());

	}
	
	function testFromNameBogus() {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Policy with name 'squid' does not exist.");
		$policy = Policy::fromName(TestHelper::getEPDO(), "squid");
	}
	
	function testFromId() {
		$policy = Policy::fromId(TestHelper::getEPDO(), 4);
		$this->assertInstanceOf(Policy::class, $policy);
		$this->assertEquals(4, $policy->getId());
		$this->assertEquals("keepv10d5month", $policy->getName());
		$this->assertEquals("2", $policy->getPartition()->getId());
		$this->assertEquals(10, $policy->getVersionExists());
		$this->assertEquals(5, $policy->getVersionDeleted());
		$this->assertEquals(31, $policy->getRetentionExists());
		$this->assertEquals(15, $policy->getRetentionDeleted());
	}
	
	function testFromIdBogus() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Policy with id '25' does not exist.");
		$policy = Policy::fromId(TestHelper::getEPDO(), 25);
	}
}
