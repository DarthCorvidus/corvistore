<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class InExTest extends TestCase {
	function testConstruct() {
		$inex = new InEx();
		$this->assertInstanceOf(InEx::class, $inex);
	}
	
	function testExclude() {
		$inex = new InEx();
		$inex->addExclude("/var/www");
		$this->assertEquals(TRUE, $inex->isExcluded("/var/www"));
		$this->assertEquals(TRUE, $inex->isExcluded("/var/www/html"));
		$this->assertEquals(FALSE, $inex->isExcluded("/home"));
	}
	
	/*
	 * If exclude is empty, all paths are allowed.
	 */
	function testExcludeEmpty() {
		$inex = new InEx();
		$this->assertEquals(FALSE, $inex->isExcluded("/var/www"));
		$this->assertEquals(FALSE, $inex->isExcluded("/var/www/html"));
		$this->assertEquals(FALSE, $inex->isExcluded("/home"));
	}
	
	function testInclude() {
		$inex = new InEx();
		$inex->addInclude("/var/www");
		$this->assertEquals(FALSE, $inex->isIncluded("/var/"));
		$this->assertEquals(TRUE, $inex->isIncluded("/var/www/"));
		$this->assertEquals(TRUE, $inex->isIncluded("/var/www/html"));
		$this->assertEquals(FALSE, $inex->isIncluded("/home"));
	}
	
	/*
	 * If include is empty, all paths are allowed.
	 */
	function testIncludeEmpty() {
		$inex = new InEx();
		$this->assertEquals(TRUE, $inex->isIncluded("/var/log/"));
	}
	
	function testTransitOnly() {
		$inex = new InEx();
		$inex->addInclude("/home/user01/Documents/work");
		$this->assertEquals(TRUE, $inex->transitOnly("/home/"));
		$this->assertEquals(TRUE, $inex->transitOnly("/home/user01/"));
		$this->assertEquals(TRUE, $inex->transitOnly("/home/user01/Documents/"));
		$this->assertEquals(FALSE, $inex->transitOnly("/home/user01/Documents/work/"));
	}
	
	function testValidExcluded() {
		$inex = new InEx();
		$inex->addExclude("/var/www");
		$this->assertEquals(FALSE, $inex->isValid("/var/www"));
		$this->assertEquals(FALSE, $inex->isValid("/var/www/html"));
		$this->assertEquals(TRUE, $inex->isValid("/home"));
	}
	
	function testValidIncluded() {
		$inex = new InEx();
		$inex->addInclude("/var/www");
		$this->assertEquals(TRUE, $inex->isValid("/var/www/"));
		$this->assertEquals(TRUE, $inex->isValid("/var/www/"));
		$this->assertEquals(TRUE, $inex->isValid("/var/www/html"));
		$this->assertEquals(FALSE, $inex->isValid("/var/"));
		$this->assertEquals(FALSE, $inex->isValid("/home"));
	}
}
