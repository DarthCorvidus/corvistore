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
	
	public function testValidate() {
		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->assertEquals(NULL, $command->import(new CPModelExample()));
	}
	
	public function testValidateUnexpectedParameterFirst() {
		$command = new CommandParser('define    storage backup-main color=red type=basic description="main backup device class" location=/storage/backup-main/');
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Parameter 'color' not valid for 'define storage'");
		$command->import(new CPModelExample());
	}

	public function testValidateUnexpectedParameterSecond() {
		$command = new CommandParser('define    storage backup-main type=basic color=red description="main backup device class" location=/storage/backup-main/');
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Parameter 'color' not valid for 'define storage'");
		$command->import(new CPModelExample());
	}

	
	public function testValidateMandatoryMissing() {
		$command = new CommandParser('define    storage backup-main description="main backup device class" location=/storage/backup-main/');
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Mandatory parameter 'type' is missing.");
		$command->import(new CPModelExample());
	}

	public function testValidateMandatoryEmpty() {
		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location=');
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Error at parameter 'location': value is mandatory");
		$command->import(new CPModelExample());
	}

	public function testValidateMandatoryInvalidPath() {
		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location='.__DIR__."/storage/basic25/");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Error at parameter 'location': path does not exist.");
		$command->import(new CPModelExample());
	}

	public function testValidateUnexpectedPositional() {
		$command = new CommandParser('define storage backup-main backup-lost type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Unexpected positional value 'backup-lost' for 'define storage'");
		$command->import(new CPModelExample());
	}
	
	public function testValidateMissingPositional() {
		$command = new CommandParser('define storage type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Missing positional value 1 for 'define storage'");
		$command->import(new CPModelExample());
	}

}
