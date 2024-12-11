<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Client\Config;
class ConfigClientTest extends TestCase {
	function testConstruct(): void {
		$config = new Config(__DIR__."/include.conf");
		$this->assertInstanceOf(Config::class, $config);
	}
	
	function testConstructBogus(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Client configuration at ".__DIR__."/squid.conf not available.");
		new Config(__DIR__."/squid.conf");
	}
	
	function testConstructDir(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Client configuration at ".__DIR__." not a file.");
		new Config(__DIR__);
	}
	
	function testGetNode(): void {
		$config = new Config(__DIR__."/include.conf");
		$this->assertEquals("test01", $config->getNode());
	}
	
	function testGetExclude(): void {
		$expected = array();
		$expected[] = "/virtual/";
		$expected[] = "/storage/";
		$expected[] = "/var/lib/crow-protect/";
		$config = new Config(__DIR__."/exclude.conf");
		$this->assertEquals($expected, $config->getExclude());
	}
	
	function testGetInclude(): void {
		$expected = array();
		$expected[] = "/home/user/";
		$config = new Config(__DIR__."/include.conf");
		$this->assertEquals($expected, $config->getInclude());
	}
	
	function testGetInExExclude(): void {
		$config = new Config(__DIR__."/exclude.conf");
		$this->assertEquals(TRUE, $config->getInEx()->isValid("/home"));
		$this->assertEquals(FALSE, $config->getInEx()->isValid("/storage"));
	}
	
	function testGetInExInclude(): void {
		$config = new Config(__DIR__."/include.conf");
		$this->assertEquals(TRUE, $config->getInEx()->isValid("/home/user"));
		$this->assertEquals(FALSE, $config->getInEx()->isValid("/storage"));
	}
	
	function testGetHost(): void {
		$config = new Config(__DIR__."/include.conf");
		$this->assertEquals("backup.example.com", $config->getHost());
	}

}
