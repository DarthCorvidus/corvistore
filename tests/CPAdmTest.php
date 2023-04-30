<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class CPAdmTest extends TestCase {
	public function testConstruct() {
		$adm = new CPAdm(TestHelper::getEPDO());
		$this->assertInstanceOf(CPAdm::class, $adm);
	}
	
	public function testGetCommand() {
		$adm = new CPAdm(TestHelper::getEPDO());
		$command = $adm->getCommand("define storage backup-main location=/storage/backup-main/ type=directory description=\"main backup storage\"");
		$this->assertEquals("define", $command->getCommand());
		$this->assertEquals("storage", $command->getObject());
		$this->assertEquals("backup-main", $command->getPositional(0));
		$this->assertEquals("directory", $command->getParam("type"));
		$this->assertEquals("main backup storage", $command->getParam("description"));
		$this->assertEquals("/storage/backup-main/", $command->getParam("location"));
	}
	
	public function testHandleCommandUnknown() {
		$adm = new CPAdm(TestHelper::getEPDO());
		$command = $adm->getCommand("yell at me");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Invalid command 'yell'.");
		$result = $adm->handleCommand($command);
	}
}
