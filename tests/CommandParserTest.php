<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class CommandParserTest extends TestCase {
	public function testSplitSimple() {
		$split = CommandParser::split("define storage backup-main type=directory location=/storage/backup-main/");
		$target = array();
		$target[] = "define";
		$target[] = "storage";
		$target[] = "backup-main";
		$target[] = "type=directory";
		$target[] = "location=/storage/backup-main/";
		$this->assertEquals($target, $split);
	}
	
	public function testSplitAdditionalWhitespace() {
		$split = CommandParser::split("define    storage backup-main type=directory location=/storage/backup-main/");
		$target = array();
		$target[] = "define";
		$target[] = "storage";
		$target[] = "backup-main";
		$target[] = "type=directory";
		$target[] = "location=/storage/backup-main/";
		$this->assertEquals($target, $split);
	}
	
	public function testSplitQuotedValue() {
		$split = CommandParser::split('define    storage backup-main type=directory description="main backup device class" location=/storage/backup-main/');
		$target = array();
		$target[] = "define";
		$target[] = "storage";
		$target[] = "backup-main";
		$target[] = "type=directory";
		$target[] = "description=main backup device class";
		$target[] = "location=/storage/backup-main/";
		$this->assertEquals($target, $split);
		
	}

	public function testSplitOpenQuote() {
		$this->expectException(Exception::class);
		$split = CommandParser::split('define    storage backup-main type=directory description="main backup device class location=/storage/backup-main/');
	}
	
	public function testGetCommand() {
		$command = new CommandParser('define    storage backup-main type=directory description="main backup device class" location=/storage/backup-main/');
		$this->assertEquals("define", $command->getCommand());
	}
	
	public function testGetObject() {
		$command = new CommandParser('define    storage backup-main type=directory description="main backup device class" location=/storage/backup-main/');
		$this->assertEquals("storage", $command->getObject());
	}
	
	public function testGetPositional() {
		$command = new CommandParser('define    storage backup-main type=directory description="main backup device class" location=/storage/backup-main/');
		$this->assertEquals("backup-main", $command->getPositional(0));
	}
	
	public function testGetParameter() {
		$command = new CommandParser('define    storage backup-main type=directory description="main backup device class" location=/storage/backup-main/');
		$this->assertEquals("directory", $command->getParam("type"));
		$this->assertEquals("main backup device class", $command->getParam("description"));
		$this->assertEquals("/storage/backup-main/", $command->getParam("location"));
	}


}
