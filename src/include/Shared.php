<?php
/**
 * Shared
 * 
 * Planned to be the central hub for depencies, such as the database connection.
 * Currently only to be used with SQLite.
 *
 * @author Claus-Christoph Küthe
 */
class Shared {
	private $pdo;
	function __construct() {
		;
	}
	
	function useSQLite(string $path) {
		$this->pdo = new EPDO("sqlite:".$path, "", "");
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	function getEPDO(): EPDO  {
		return $this->pdo;
	}
}
