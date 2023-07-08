<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class DefineHandlerTest extends TestCase {
	function __construct() {
		parent::__construct();
	}
	
	function setUp() {
		TestHelper::resetDatabase();
		TestHelper::resetStorage();
	}

	function testInvalidDefine() {
		$defineCommand = new CommandParser("define cake type=cheesecake diameter=28cm");
		$handler = new DefineHandler(TestHelper::getEPDO(), $defineCommand);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("'define cake' is not a valid command.");
		$handler->run();
	}
	
	function testDefineStorage() {
		$command = new CommandParser("define storage backup-main type=basic location=".__DIR__."/storage/basic01/");
		$query = new DefineHandler(TestHelper::getEPDO(), $command);
		$query->run();
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_storage", "dst_id");
		$target[0] = array("dst_id" => 1, "dst_name" => "backup-main", "dst_location"=>__DIR__."/storage/basic01/", "dst_type"=>"basic");
		$this->assertEquals($target, $database);
	}
	
	function testDefinePartition() {
		$command = new CommandParser("define storage backup-main type=basic location=".__DIR__."/storage/basic01/");
		$query = new DefineHandler(TestHelper::getEPDO(), $command);
		$query->run();

		$command = new CommandParser("define partition primary type=common storage=backup-main");
		$define = new DefineHandler(TestHelper::getEPDO(), $command);
		$define->run();

		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_partition", "dpt_id");
		$target[0] = array("dpt_id" => 1, "dst_id" => 1, "dpt_name" => "primary", "dpt_type" => "common", "dst_id" => 1, "dpt_copy" => NULL, "dpt_nextpt" => NULL);
		$this->assertEquals($target, $database);
	}
	
	function testDefinePolicy() {
		$command = new CommandParser("define storage backup-main type=basic location=".__DIR__."/storage/basic01/");
		$query = new DefineHandler(TestHelper::getEPDO(), $command);
		$query->run();

		$command = new CommandParser("define partition primary type=common storage=backup-main");
		$define = new DefineHandler(TestHelper::getEPDO(), $command);
		$define->run();
		
		$define = new DefineHandler(TestHelper::getEPDO(), new CommandParser("define policy keepv10d5month partition=primary verexists=10 verdeleted=5 retexists=31 retdeleted=15"));
		$define->run();
		$target[0] = array("dpo_id" => "1", "dpo_name"=>"keepv10d5month", "dpo_version_exists" => "10", "dpo_version_deleted"=>"5", "dpo_retention_exists" => "31", "dpo_retention_deleted"=>"15", "dpt_id"=>"1");
		$this->assertEquals($target, TestHelper::dumpTable(TestHelper::getEPDO(), "d_policy", "dpo_id"));
	}
	
	function testDefineNode() {
		$command = new CommandParser("define storage backup-main type=basic location=".__DIR__."/storage/basic01/");
		$query = new DefineHandler(TestHelper::getEPDO(), $command);
		$query->run();

		$command = new CommandParser("define partition primary type=common storage=backup-main");
		$define = new DefineHandler(TestHelper::getEPDO(), $command);
		$define->run();
		
		$define = new DefineHandler(TestHelper::getEPDO(), new CommandParser("define policy keepv10d5month partition=primary verexists=10 verdeleted=5 retexists=31 retdeleted=15"));
		$define->run();

		$define = new DefineHandler(TestHelper::getEPDO(), new CommandParser("define node test01 policy=keepv10d5month password=secret"));
		$define->run();
		/*
		 * This somehow defeats the intention of the test, as Node::fromName may
		 * fail and cause to fail the test early. However, I need to get
		 * the password hash and salt, as the salt is non-deterministic.
		 */
		$node = Node::fromName(TestHelper::getEPDO(), "test01");
		$target[0] = array("dnd_id" => "1", "dnd_name"=>"test01", "dpo_id"=>"1", "dnd_password" => $node->getPassword(), "dnd_salt" => $node->getSalt());
		$this->assertEquals($target, TestHelper::dumpTable(TestHelper::getEPDO(), "d_node", "dnd_id"));

	}

}
