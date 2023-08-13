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
	function __construct() {
		;
	}
	
	static function getCustomSQLite(string $path) {
		Assert::fileExists($path);
		$pdo = new EPDO("sqlite:".$path, "", "");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $pdo;
	}
	
	static function getHomePath(): string {
		return $_SERVER["HOME"];
	}
	
	static function getInstancePath(): string {
		return self::getHomePath()."/cpinst";
	}
	
	static function getDatabasePath() {
		return self::getInstancePath()."/database";
	}

	static function getDatabaseFile() {
		return self::getDatabasePath()."/crow-protect.sqlite";
	}

	static function getIPCSocket() {
		return self::getInstancePath()."/ssl-server.socket";
	}
	
	static function getSSLPath() {
		return self::getInstancePath()."/ssl";
	}
	
	static function getSSLAuthorityFile() {
		return self::getSSLPath()."/ca.crt";
	}

	static function getSSLServerCertificate() {
		return self::getSSLPath()."/server.crt";
	}

	static function getSSLServerKey() {
		return self::getSSLPath()."/server.key";
	}
	
	static function getEPDO(): EPDO {
		echo self::getDatabaseFile().PHP_EOL;
		if(!file_exists(self::getDatabaseFile())) {
			throw new RuntimeException("database file ".self::getDatabaseFile()." does not exist");
		}
		$pdo = new EPDO("sqlite:".self::getDatabaseFile(), "", "");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $pdo;
	}
}
