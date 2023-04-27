<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class DefineHandlerTest extends TestCase {
	function __construct() {
		parent::__construct();
		$this->now = mktime();
	}
	static function setUpBeforeClass() {
		TestHelper::resetDatabase();
	}
	
	function testInvalidDefine() {
		$defineCommand = new CommandParser("define cake type=cheesecake diameter=28cm");
		$handler = new DefineHandler(TestHelper::getEPDO(), $defineCommand);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("'define cake' is not a valid command.");
		$handler->run();
	}
	
	function testDefineStorage() {
		$command = new CommandParser("define storage backup-main type=basic location=".__DIR__."/storage/backup-main/");
		$query = new DefineHandler(TestHelper::getEPDO(), $command);
		$query->run();
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_storage", "dst_id");
		$target[0] = array("dst_id" => 1, "dst_name" => "backup-main", "dst_location"=>__DIR__."/storage/backup-main/", "dst_type"=>"basic");
		$this->assertEquals($target, $database);
	}
	
	function testDefinePartition() {
		$command = new CommandParser("define partition primary type=common storage=backup-main");
		$define = new DefineHandler(TestHelper::getEPDO(), $command);
		$define->run();
		$database = TestHelper::dumpTable(TestHelper::getEPDO(), "d_partition", "dpt_id");
		$target[0] = array("dpt_id" => 1, "dst_id" => 1, "dpt_name" => "primary", "dpt_type" => "common", "dst_id" => 1);
		$this->assertEquals($target, $database);
	}
}
