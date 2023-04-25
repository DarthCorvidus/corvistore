<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class CPAdmTest extends TestCase {
	public function testConstruct() {
		$adm = new CPAdm();
		$this->assertInstanceOf(CPAdm::class, $adm);
	}
	
	public function testGetCommand() {
		$adm = new CPAdm();
		$command = $adm->getCommand("define storage backup-main location=/storage/backup-main/ type=directory description=\"main backup storage\"");
		$this->assertEquals("define", $command->getCommand());
		$this->assertEquals("storage", $command->getObject());
		$this->assertEquals("backup-main", $command->getPositional(0));
		$this->assertEquals("directory", $command->getParam("type"));
		$this->assertEquals("main backup storage", $command->getParam("description"));
		$this->assertEquals("/storage/backup-main/", $command->getParam("location"));
	}
	
	public function testHandleCommand() {
		$adm = new CPAdm();
		$command = $adm->getCommand("define storage backup-main location=/storage/backup-main/ type=directory description=\"main backup storage\"");
		$this->expectOutputString("I don't know to handle anything yet.".PHP_EOL);
		$result = $adm->handleCommand($command);
	}
}
