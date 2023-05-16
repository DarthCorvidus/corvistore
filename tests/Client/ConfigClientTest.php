<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Client\Config;
class ConfigClientTest extends TestCase {
	function testConstruct() {
		$config = new Config(__DIR__."/include.yml");
		$this->assertInstanceOf(Config::class, $config);
	}
	
	function testConstructBogus() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Client configuration at ".__DIR__."/squid.yml not available.");
		new Config(__DIR__."/squid.yml");
	}
	
	function testConstructDir() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Client configuration at ".__DIR__." not a file.");
		new Config(__DIR__);
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
	
	function testGetInExExclude() {
		$config = new Config(__DIR__."/exclude.yml");
		$this->assertEquals(TRUE, $config->getInEx()->isValid("/home"));
		$this->assertEquals(FALSE, $config->getInEx()->isValid("/storage"));
	}
	
	function testGetInExInclude() {
		$config = new Config(__DIR__."/include.yml");
		$this->assertEquals(TRUE, $config->getInEx()->isValid("/home/user"));
		$this->assertEquals(FALSE, $config->getInEx()->isValid("/storage"));
	}

}
