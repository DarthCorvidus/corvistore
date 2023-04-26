<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class QueryHandlerTest extends TestCase {
	function __construct() {
		parent::__construct();
		$this->now = mktime();
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
	
	function testQueryStorage() {
		$command01 = new CommandParser("define storage storage01 type=basic location=".__DIR__."/storage/backup-main/");
		StorageBasic::define(TestHelper::getEPDO(), $command01);
		$command02 = new CommandParser("define storage storage02 type=basic location=".__DIR__."/storage/backup-copy/");
		StorageBasic::define(TestHelper::getEPDO(), $command02);
		$command03 = new CommandParser("define storage storage03 type=basic location=".__DIR__."/storage/backup-cloud/");
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
}
