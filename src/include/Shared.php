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
	
	static function getDatabasePath() {
		return self::getHomePath()."/cpinst/database/";
	}

	static function getSSLPath() {
		return self::getHomePath()."/cpinst/ssl/";
	}
	
	static function getSSLAuthorityFile() {
		return self::getSSLPath()."ca.crt";
	}

	static function getSSLServerCertificate() {
		return self::getSSLPath()."server.crt";
	}

	static function getSSLServerKey() {
		return self::getSSLPath()."server.key";
	}
	
	static function getEPDO(): EPDO {
		if(!file_exists(self::getDatabasePath())) {
			throw new RuntimeException("database path does not exist");
		}
		$pdo = new EPDO("sqlite:".self::getDatabasePath()."crow-protect.sqlite", "", "");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $pdo;
	}
}
