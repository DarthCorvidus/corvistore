<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \plibv4\Binary\StringWriter;
use \plibv4\Binary\StringReader;

class FileTransportContainerTest extends TestCase {
	public function setUp(): void {
		if (!file_exists("/tmp/corviprotect")) {
			mkdir("/tmp/corviprotect");
		}
		file_put_contents("/tmp/corviprotect/file1.bin", "The cat is on the mat");
		$file = File::fromPath("/tmp/corviprotect/file1.bin");
		$binary = $file->toBinary();
		file_put_contents("/tmp/corviprotect/file2.bin", str_pad($binary, 8192, "\0") . "The cat is on the mat");
	}

	public function tearDown(): void {
		unlink("/tmp/corviprotect/file1.bin");
		unlink("/tmp/corviprotect/file2.bin");
		rmdir("/tmp/corviprotect");
	}

	function testFromPath(): void {
		$file = File::fromPath("/tmp/corviprotect/file1.bin");
		$fc = FileTransportContainer::fromFile($file);
		$this->assertSame($file, $fc->getFile());
		$this->assertSame("The cat is on the mat", $fc->getData());
	}

	function testToBinary(): void {
		$file = File::fromPath("/tmp/corviprotect/file1.bin");
		$fc = FileTransportContainer::fromFile($file);
		$binary = $fc->toBinary();
		$reader = new StringReader($binary, StringReader::LE);
		$meta = File::fromBinary($reader->getIndexedString(16));
		$data = $reader->getIndexedString(32);
		$this->assertEquals($file, $meta);
		$this->assertSame("The cat is on the mat", $data);
	}

	function testFromBinary(): void {
		$file = File::fromPath("/tmp/corviprotect/file1.bin");
		$fc = FileTransportContainer::fromFile($file);
		$binary = $fc->toBinary();
		$fc2 = FileTransportContainer::fromBinary($binary);
		$this->assertEquals($fc, $fc2);
	}

	function testFromStoredFile(): void {
		/**
		 * We have to use the original file1.bin to create the File object, as this
		 * path was used to create the File object in the first place.
		 */
		$file = File::fromPath("/tmp/corviprotect/file1.bin");
		$fc = FileTransportContainer::fromStoredFile(file_get_contents("/tmp/corviprotect/file2.bin"));
		$this->assertEquals($file, $fc->getFile());
		$this->assertSame("The cat is on the mat", $fc->getData());
	}
}
