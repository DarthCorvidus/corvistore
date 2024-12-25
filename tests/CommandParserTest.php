<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class CommandParserTest extends TestCase {
	static function setUpBeforeClass(): void {
		mkdir(__DIR__."/storage/basic01");
	}

	static function tearDownAfterClass(): void {
		rmdir(__DIR__."/storage/basic01");
	}
	
	public function testSplitSimple(): void {
		$split = CommandParser::split("define storage backup-main type=directory location=/storage/backup-main/");
		$target = array();
		$target[] = "define";
		$target[] = "storage";
		$target[] = "backup-main";
		$target[] = "type=directory";
		$target[] = "location=/storage/backup-main/";
		$this->assertEquals($target, $split);
	}
	
	public function testSplitAdditionalWhitespace(): void {
		$split = CommandParser::split("define    storage backup-main type=directory location=/storage/backup-main/");
		$target = array();
		$target[] = "define";
		$target[] = "storage";
		$target[] = "backup-main";
		$target[] = "type=directory";
		$target[] = "location=/storage/backup-main/";
		$this->assertEquals($target, $split);
	}
	
	public function testSplitQuotedValue(): void {
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

	public function testSplitOpenQuote(): void {
		$this->expectException(Exception::class);
		CommandParser::split('define    storage backup-main type=directory description="main backup device class location=/storage/backup-main/');
	}
	
	public function testGetCommand(): void {
		$command = new CommandParser('define    storage backup-main type=directory description="main backup device class" location=/storage/backup-main/');
		$this->assertEquals("define", $command->getCommand());
	}
	
	public function testGetObject(): void {
		$command = new CommandParser('define    storage backup-main type=directory description="main backup device class" location=/storage/backup-main/');
		$this->assertEquals("storage", $command->getObject());
	}
	
	public function testGetPositional(): void {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addParamUserValue("location", UserValue::asMandatory());
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
		
		$command = new CommandParser('define    storage backup-main type=basic description="main backup device class" location='.__DIR__.'/storage/basic01');
		$command->import($cpmodel);
		$this->assertEquals("backup-main", $command->getPositional(0));
	}
	
	public function testGetParameter(): void {
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
	
	public function testValidatePass(): void {
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

	public function testValidateUnexpectedParameterFirst(): void {
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

	public function testValidateUnexpectedParameterSecond(): void {
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

	
	public function testValidateMandatoryMissing(): void {
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

	public function testValidateMandatoryEmpty(): void {
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

	public function testValidateFail(): void {
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

	public function testValidateUnexpectedPositionalFirst(): void {
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

	public function testValidateUnexpectedPositionalSecond(): void {
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
	
	public function testValidateMissingPositional(): void {
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

	public function testAccessParamWithoutImport(): void {
		$command = new CommandParser('define storage example type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Accessing named parameter 'type' without calling CommandParser::import()");
		$command->getParam("type");
	}
	
	public function testAccessPositionalWithoutImport(): void {
		$command = new CommandParser('define storage example type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Accessing positional parameter '0' without calling CommandParser::import()");
		$command->getPositional(0);
	}

	public function testGetDefaultValue(): void {
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
	public function testGetDefaultValueMandatoryEmpty(): void {
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
	public function testGetDefaultValueOptionalEmpty(): void {
		$cpmodel = new CPModelTesting();

		$description = UserValue::asOptional();
		$description->setDefault("My main backup storage");
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
	
		$command = new CommandParser('update storage backup-main description=');
		$command->import($cpmodel);
		$this->assertEquals("", $command->getParam("description"));
	}
	
	function testConvert(): void {
		$cpmodel = new CPModelTesting();
		$cpmodel->addParamUserValue("description", UserValue::asOptional());
		$location = UserValue::asMandatory();
		$location->setValidate(new ValidatePath(ValidatePath::DIR));
		$location->setConvert(new ConvertTrailingSlash());
		
		$cpmodel->addParamUserValue("location", $location);
		$cpmodel->addParamUserValue("type", UserValue::asMandatory());
		$cpmodel->addPositionalUserValue(UserValue::asMandatory());
	
		$command = new CommandParser('define storage example type=basic description="main backup device class" location='.__DIR__."/storage/basic01/");
		$command->import($cpmodel);
		$this->assertEquals(__DIR__."/storage/basic01", $command->getParam("location"));
	}
}
