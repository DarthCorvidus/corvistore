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
	
	/**
	 * Converts a path into 'breadcrumbs' of consecutively longer paths, ie
	 * /etc/something/somewhere becomes [/etc, /etc/something, /etc/something/somewhere].
	 * @param string $path must not be empty string or root directory
	 * @return list<string>
	 * @throws \RuntimeException
	 */
	public static function getBreadcrumbs(string $path): array {
		if($path === "") {
			throw new \RuntimeException("empty string as path");
		}
		if($path === "/") {
			throw new \RuntimeException("unexpected '/' as path");
		}
		if($path[0] !== "/") {
			throw new \RuntimeException("path must have a leading '/'");
		}
		$breadcrumbs = array();
		$convert = new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE);
		$untrailed = $convert->convert($path);
		$exp = explode("/", $untrailed);
		$sub = array_slice($exp, 1);
		$previous = "";
		foreach($sub as $value) {
			$breadcrumbs[] = $previous."/".$value;
			$previous = $previous."/".$value;
		}
	return $breadcrumbs;
	}
	
	static function getCustomSQLite(string $path): EPDO {
		Assert::fileExists($path);
		$pdo = new EPDO("sqlite:".$path, "", "");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $pdo;
	}
	
	static function getHomePath(): string {
		if(!isset($_SERVER["HOME"])) {
			throw new \RuntimeException("unable to determine home path, please check \$HOME.");
		}
		$home = $_SERVER["HOME"];
	return $home;
	}
	
	static function getInstancePath(): string {
		return self::getHomePath()."/cpinst";
	}
	
	static function getDatabasePath(): string {
		return self::getInstancePath()."/database";
	}

	static function getDatabaseFile(): string {
		return self::getDatabasePath()."/crow-protect.sqlite";
	}

	static function getIPCSocket(): string {
		return self::getInstancePath()."/ssl-server.socket";
	}
	
	static function getSSLPath(): string {
		return self::getInstancePath()."/ssl";
	}
	
	static function getSSLAuthorityFile(): string {
		return self::getSSLPath()."/ca.crt";
	}

	static function getSSLServerCertificate(): string {
		return self::getSSLPath()."/server.crt";
	}

	static function getSSLServerKey(): string {
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
