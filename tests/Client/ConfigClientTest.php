<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Client\Config;
class ConfigClientTest extends TestCase {
	function testConstruct() {
		$config = new Config(__DIR__."/include.yml");
		$this->assertInstanceOf(Config::class, $config);
	}
	
	function testGetNode() {
		$config = new Config(__DIR__."/include.yml");
		$this->assertEquals("test01", $config->getNode());
	}
	
	function testGetExclude() {
		$expected[] = "/virtual/";
		$expected[] = "/storage/";
		$expected[] = "/var/lib/crow-protect/";
		$config = new Config(__DIR__."/exclude.yml");
		$this->assertEquals($expected, $config->getExclude());
	}
	
	function testGetInclude() {
		$expected[] = "/home/user/";
		$config = new Config(__DIR__."/include.yml");
		$this->assertEquals($expected, $config->getInclude());
	}
}
