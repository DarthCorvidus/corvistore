<?php
/**
 * TestHelper
 * 
 * Serves as a helper function for all tests, resetting database contents as
 * well as dumping table contents to compare them with expected values.
 *
 * @author Claus-Christoph Küthe
 */
class TestHelper {
	static function dumpTable(EPDO $pdo, $table, $sort) {
		$database = array();
		$stmt = $pdo->prepare("select * from ".$table." order by ".$sort);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		foreach($stmt as $key => $value) {
			$database[$key] = $value;
		}
	return $database;
	}
	
	static function createDatabase() {
		$database = __DIR__."/test.sqlite";
		$template = __DIR__."/../default-sqlite.sql";
		if(file_exists($database)) {
			throw new Exception("Database already exists");
		}
		exec("cat ". escapeshellarg($template)." | sqlite3 ". escapeshellarg($database));
	}
	
	static function deleteDatabase() {
		unlink(__DIR__."/test.sqlite");
	}
	
	static function resetSerial() {
		$database = __DIR__."/serial.sqlite";
		$template = __DIR__."/../test-serial.sql";
		if(file_exists($database)) {
			unlink($database);
		}
		exec("cat ". escapeshellarg($template)." | sqlite3 ". escapeshellarg($database));
	}
	
	static function getEPDO(): EPDO {
	return Shared::getCustomSQLite(__DIR__."/test.sqlite");
	}
	
	static function createStorage() {
		$storage = array("basic01", "basic02", "basic03");
		foreach($storage as $value) {
			mkdir(__DIR__."/storage/".$value);
		}
	}
	
	static function deleteStorage() {
		$storage = array("basic01", "basic02", "basic03");
		foreach($storage as $value) {
			$storagePath = __DIR__."/storage/".$value;
			if(file_exists($storagePath)) {
				exec("rm ".escapeshellarg($storagePath)." -r");
			}
		}
	}
	
	static function initServer() {
		self::createStorage();
		$cpadm = new CPAdm(TestHelper::getEPDO(), array());
		$cpadm->handleCommand(new CommandParser("define storage basic01 type=basic location=".__DIR__."/storage/basic01/"));
		$cpadm->handleCommand(new CommandParser("define partition backup-main type=common storage=basic01"));
		$cpadm->handleCommand(new CommandParser("define policy forever partition=backup-main"));
		$cpadm->handleCommand(new CommandParser("define node test01 policy=forever password=secret"));
	}
	
	static function invoke($object, $method, array $args) {
		$reflector = new ReflectionClass(get_class($object));
		$method = $reflector->getMethod($method);
		$method->setAccessible(true);
	return $method->invokeArgs($object, $args);
	}
	
	static function getPropertyValue($object, $propertyName) {
		$reflector = new ReflectionObject($object);
		$property = $reflector->getProperty($propertyName);
		$property->setAccessible(true);
	return $property->getValue($object);
	}
	
	
	static function fileowner($filename) {
		$owner = posix_getpwuid(fileowner($filename));
		
	return $owner["name"];
	}
	
	static function filegroup($filename) {
		$group = posix_getgrgid(filegroup($filename));
	return $group["name"];
	}
	
	static function binaryAsHex(string $string): string {
		$hex = "0x";
		$len = strlen($string);
		for($i=0;$i<$len;$i++) {
			$hex .= bin2hex($string[$i]);
		}
	return $hex;
	}
}
