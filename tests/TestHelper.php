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
	
	static function resetDatabase() {
		$database = __DIR__."/test.sqlite";
		$template = __DIR__."/../default-sqlite.sql";
		if(file_exists($database)) {
			unlink($database);
		}
		exec("cat ". escapeshellarg($template)." | sqlite3 ". escapeshellarg($database));
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
		$shared = new Shared();
		$shared->useSQLite(__DIR__."/test.sqlite");
		return $shared->getEPDO();
	}
}
