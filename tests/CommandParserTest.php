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
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addParamUserValue("location", UserValue::asMandatory());
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
		
		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location='.__DIR__.'/storage/basic01');
		$command->import($cpmodel);
		$this->assertEquals("backup-main", $command->getPositional(0));
	}
	
	public function testGetParameter() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addParamUserValue("location", UserValue::asMandatory());
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());

		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location='.__DIR__.'/storage/basic01');
		$command->import($cpmodel);
		$this->assertEquals("basic", $command->getParam("type"));
		$this->assertEquals("main backup device class", $command->getParam("description"));
		$this->assertEquals(__DIR__."/storage/basic01", $command->getParam("location"));
	}
	
	public function testValidatePass() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$cpmodel->addParamUserValue("location", $location);
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());

		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->assertEquals(NULL, $command->import($cpmodel));
	}

	public function testValidateUnexpectedParameterFirst() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addParamUserValue("location", UserValue::asMandatory());
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
		
		$command = new CommandParser('define    storage backup-main color=red type=basic description="main backup device class" location=/storage/backup-main/');
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Parameter 'color' not valid for 'define storage'");
		$command->import($cpmodel);
	}

	public function testValidateUnexpectedParameterSecond() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addParamUserValue("location", UserValue::asMandatory());
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
		
		$command = new CommandParser('define    storage backup-main type=basic color=red description="main backup device class" location=/storage/backup-main/');
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Parameter 'color' not valid for 'define storage'");
		$command->import($cpmodel);
	}

	
	public function testValidateMandatoryMissing() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addParamUserValue("location", UserValue::asMandatory());
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());

		$command = new CommandParser('define    storage backup-main description="main backup device class" location=/storage/backup-main/');
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Mandatory parameter 'type' is missing.");
		$command->import($cpmodel);
	}

	public function testValidateMandatoryEmpty() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addParamUserValue("location", UserValue::asMandatory());
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
		
		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location=');
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Error at parameter 'location': value is mandatory");
		$command->import($cpmodel);
	}

	public function testValidateFail() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$cpmodel->addParamUserValue("location", $location);
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());

		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location='.__DIR__."/storage/basic25/");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Error at parameter 'location': path does not exist.");
		$command->import($cpmodel);
	}

	public function testValidateUnexpectedPositionalFirst() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$cpmodel->addParamUserValue("location", $location);
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		
		$command = new CommandParser('define storage backup-main type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Unexpected positional value 'backup-main' for 'define storage'");
		$command->import($cpmodel);
	}

	public function testValidateUnexpectedPositionalSecond() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$cpmodel->addParamUserValue("location", $location);
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
		
		$command = new CommandParser('define storage backup-main backup-lost type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Unexpected positional value 'backup-lost' for 'define storage'");
		$command->import($cpmodel);
	}
	
	public function testValidateMissingPositional() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$cpmodel->addParamUserValue("location", $location);
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
	
		$command = new CommandParser('define storage type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Missing positional value 1 for 'define storage'");
		$command->import($cpmodel);
	}

	public function testAccessParamWithoutImport() {
		$command = new CommandParser('define storage example type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Accessing named parameter 'type' without calling CommandParser::import()");
		$command->getParam("type");
	}
	
	public function testAccessPositionalWithoutImport() {
		$command = new CommandParser('define storage example type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Accessing positional parameter '0' without calling CommandParser::import()");
		$command->getPositional(0);
	}

	public function testGetDefaultValue() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$location->setDefault(__DIR__."/");
		$cpmodel->addParamUserValue("location", $location);
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
	
		$command = new CommandParser('define storage backup-main type=basic description="main backup device class"');
		$command->import($cpmodel);
		$this->assertEquals(__DIR__."/", $command->getParam("location"));
	}
	/**
	 * The user is not allowed to clear out mandatory values
	 */
	public function testGetDefaultValueMandatoryEmpty() {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$location->setDefault(__DIR__."/");
		$cpmodel->addParamUserValue("location", $location);
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
	
		$command = new CommandParser('define storage backup-main type=basic description="main backup device class" location=');
		$this->expectExceptionMessage("Error at parameter 'location': value is mandatory");
		$command->import($cpmodel);
	}

	/*
	 * The user is allowed to clear out optional values or to leave them empty.
	 */
	public function testGetDefaultValueOptionalEmpty() {
		$cpmodel = new CPModelTesting();

		$description = UserValue::asOptional();
		$description->setDefault("My main backup storage");
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
	
		$command = new CommandParser('update storage backup-main description=');
		$command->import($cpmodel);
		$this->assertEquals("", $command->getParam("description"));
	}

}
