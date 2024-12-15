<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class SharedTest extends TestCase {
	function testBreadcrumbsTrailing(): void {
		$path = "/home/johndoe/Pictures/vacations/2020/maledives/beach/";
		$expected = array();
		$expected[] = "/home";
		$expected[] = "/home/johndoe";
		$expected[] = "/home/johndoe/Pictures";
		$expected[] = "/home/johndoe/Pictures/vacations";
		$expected[] = "/home/johndoe/Pictures/vacations/2020";
		$expected[] = "/home/johndoe/Pictures/vacations/2020/maledives";
		$expected[] = "/home/johndoe/Pictures/vacations/2020/maledives/beach";
		self::assertSame($expected, Shared::getBreadcrumbs($path));
	}

	function testBreadcrumbsNoTrailing(): void {
		$path = "/home/johndoe/Pictures/vacations/2020/maledives/beach";
		$expected = array();
		$expected[] = "/home";
		$expected[] = "/home/johndoe";
		$expected[] = "/home/johndoe/Pictures";
		$expected[] = "/home/johndoe/Pictures/vacations";
		$expected[] = "/home/johndoe/Pictures/vacations/2020";
		$expected[] = "/home/johndoe/Pictures/vacations/2020/maledives";
		$expected[] = "/home/johndoe/Pictures/vacations/2020/maledives/beach";
		self::assertSame($expected, Shared::getBreadcrumbs($path));
	}
	
	function testBreadcrumbsEmpty(): void {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("empty string as path");
		Shared::getBreadcrumbs("");
	}

	function testBreadcrumbsRoot(): void {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("unexpected '/' as path");
		Shared::getBreadcrumbs("/");
	}

	function testBreadcrumbsNonRooted(): void {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("path must have a leading '/'");
		Shared::getBreadcrumbs("etc/something/somewhere");
	}

}