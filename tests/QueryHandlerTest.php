<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class QueryHandlerTest extends TestCase {
	function __construct() {
		parent::__construct();
	}
	static function setUpBeforeClass() {
		TestHelper::resetDatabase();
	}
	
	function testInvalidQuery() {
		$queryCommand = new CommandParser("query cake type=cheesecake diameter=28cm");
		$query = new QueryHandler(TestHelper::getEPDO(), $queryCommand);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("query cake is not a valid query");
		$query->getResult();
	}
	/*
	function testQueryStorage() {
		$command01 = new CommandParser("define storage storage01 type=basic location=".__DIR__."/storage/basic01/");
		StorageBasic::define(TestHelper::getEPDO(), $command01);
		$command02 = new CommandParser("define storage storage02 type=basic location=".__DIR__."/storage/basic02/");
		StorageBasic::define(TestHelper::getEPDO(), $command02);
		$command03 = new CommandParser("define storage storage03 type=basic location=".__DIR__."/storage/basic03/");
		StorageBasic::define(TestHelper::getEPDO(), $command03);
		$queryCommand = new CommandParser("query storage");
		$query = new QueryHandler(TestHelper::getEPDO(), $queryCommand);
		// Since we haven't implemented capacity, use 0 GiB here.
		$expect =  "Name      Type  Capacity Used ".PHP_EOL;
		$expect .= "storage01 basic    0 GiB 0 GiB".PHP_EOL;
		$expect .= "storage02 basic    0 GiB 0 GiB".PHP_EOL;
		$expect .= "storage03 basic    0 GiB 0 GiB".PHP_EOL;
		$this->assertEquals($expect, $query->getResult());
	}
	
	function testQueryPartition() {
		$command01 = new CommandParser("define partition primary storage=storage01 type=common");
		$command02 = new CommandParser("define partition secondary storage=storage02 type=common");
		Partition::define(TestHelper::getEPDO(), $command01);
		Partition::define(TestHelper::getEPDO(), $command02);

		$queryCommand = new CommandParser("query partition");
		$query = new QueryHandler(TestHelper::getEPDO(), $queryCommand);
		// Since we haven't implemented capacity, use 0 GiB here.
		$expect =  "Name      Storage   Type   Capacity Used".PHP_EOL;
		$expect .= "primary   storage01 common    0 GiB    0".PHP_EOL;
		$expect .= "secondary storage02 common    0 GiB    0".PHP_EOL;
		$this->assertEquals($expect, $query->getResult());
	}
	*/
}
