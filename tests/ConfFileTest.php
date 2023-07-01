<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class ConfFileTest extends TestCase {
	function testFromFile() {
		$expect["host"] = "backup.example.com";
		$expect["node"] = "desktop01";
		$expect["password1"] = "squishthis:!";
		$expect["password2"] = "squishthis!:";
		$expect["include"] = array("/home/", "/var/", "/etc/");
		$expect["log"] = "/var/log/crow-protect.log";
		$this->assertEquals($expect, ConfFile::fromFile(__DIR__."/example.conf"));
	}
	
	function testFromString() {
		$expect["host"] = "backup.example.com";
		$expect["node"] = "desktop01";
		$expect["password1"] = "squishthis:!";
		$expect["password2"] = "squishthis!:";
		$expect["include"] = array("/home/", "/var/", "/etc/");
		$expect["log"] = "/var/log/crow-protect.log";
		$string = "host: backup.example.com".PHP_EOL;
		$string .= "node: desktop01".PHP_EOL;
		$string .= "password1: squishthis:!".PHP_EOL;
		$string .= "password2: squishthis!:".PHP_EOL;
		$string .= "include:".PHP_EOL;
		$string .= "\t/home/".PHP_EOL;
		$string .= " /var/".PHP_EOL;
		$string .= "   /etc/".PHP_EOL;
		$string .= "log: /var/log/crow-protect.log".PHP_EOL;
		$this->assertEquals($expect, ConfFile::fromString($string));
	}
	
	function testNoColon() {
		$string = "host: backup.example.com".PHP_EOL;
		$string .= "node".PHP_EOL;
		$string .= "password1: squishthis:!".PHP_EOL;
		$string .= "password2: squishthis!:".PHP_EOL;
		$string .= "include:".PHP_EOL;
		$string .= "\t/home/".PHP_EOL;
		$string .= " /var/".PHP_EOL;
		$string .= "   /etc/".PHP_EOL;
		$string .= "log: /var/log/crow-protect.log".PHP_EOL;
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("invalid key 'node' in line 2");
		ConfFile::fromString($string);
	}

	function testLonelyList() {
		$string = "host: backup.example.com".PHP_EOL;
		$string .= "node: desktop01".PHP_EOL;
		$string .= "password1: squishthis:!".PHP_EOL;
		$string .= "password2: squishthis!:".PHP_EOL;
		$string .= "\t/home/".PHP_EOL;
		$string .= " /var/".PHP_EOL;
		$string .= "   /etc/".PHP_EOL;
		$string .= "log: /var/log/crow-protect.log".PHP_EOL;
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("isolated list value in line 5");
		ConfFile::fromString($string);
	}
	
}