<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class CPAdmTest extends TestCase {
	public function setUp(): void {
		TestHelper::createDatabase();
		TestHelper::createStorage();
	}
	
	public function tearDown(): void {
		TestHelper::deleteDatabase();
		TestHelper::deleteStorage();
	}
	public function testConstruct(): void {
		$adm = new CPAdm(TestHelper::getEPDO(), array());
		$this->assertInstanceOf(CPAdm::class, $adm);
	}
	
	public function testGetCommand(): void {
		$adm = new CPAdm(TestHelper::getEPDO(), array());
		$command = $adm->getCommand("define storage backup-main location=".__DIR__."/storage/basic01 type=basic description=\"main backup storage\"");
		$command->import(new CPModelStorage());
		$this->assertEquals("define", $command->getCommand());
		$this->assertEquals("storage", $command->getObject());
		$this->assertEquals("backup-main", $command->getPositional(0));
		$this->assertEquals("basic", $command->getParam("type"));
		$this->assertEquals("main backup storage", $command->getParam("description"));
		$this->assertEquals(__DIR__."/storage/basic01", $command->getParam("location"));
	}
	
	public function testHandleCommandUnknown(): void {
		$adm = new CPAdm(TestHelper::getEPDO(), array());
		$command = $adm->getCommand("yell at me");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Invalid command 'yell'.");
		$adm->handleCommand($command);
	}
}
