<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use \plibv4\Binary\StringWriter;
use \plibv4\Binary\StringReader;
class FileGroupTest extends TestCase {
	private array $sizes = [163107, 428421, 193744, 881860, 764330, 739675, 492817, 734690, 516814, 576884];
	public function setUp(): void {
		$mf = new MockupFiles("/tmp/corviprotect");
		foreach($this->sizes as $key => $size) {
			$mf->createRandom("file".$key.".bin", $size, 1);
		}
	}
	
	public function tearDown(): void {
		$mf = new MockupFiles("/tmp/corviprotect");
		$mf->clear();
	}
	
	public function testGetFileCount(): void {
		$fg = new FileGroup();
		foreach(glob("/tmp/corviprotect/file*.bin") as $value) {
			$container = FileTransportContainer::fromFile(File::fromPath($value));
			$fg->addFile($container);
		}
		$this->assertSame(10, $fg->getFileCount());
	}
	
	public function testGetPayloadSize(): void {
		$fg = new FileGroup();
		foreach(glob("/tmp/corviprotect/file*.bin") as $value) {
			$file = File::fromPath($value);
			$fg->addFile(FileTransportContainer::fromFile($file));
		}
		$this->assertSame(array_sum($this->sizes), $fg->getPayloadSize());
	}

	public function testToBinary(): void {
		$fg = new FileGroup();
		$origFiles = array();
		$origData = array();
		$loadedFiles = array();
		$loadedData = array();
		foreach(glob("/tmp/corviprotect/file*.bin") as $value) {
			$file = File::fromPath($value);
			$fg->addFile(FileTransportContainer::fromFile($file));
			$origFiles[] = $file;
			$origData[] = file_get_contents($value);
		}
		$binary = $fg->toBinary();
		$reader = new StringReader($binary, StringReader::LE);
		$count = $reader->getUInt8();
		$this->assertEquals($count, 10);
		for($i = 0; $i<$count; $i++) {
			$tc = FileTransportContainer::fromBinary($reader->getIndexedString(32));
			$loadedFiles[] = $tc->getFile();
			$loadedData[] = $tc->getData();
		}
		for($i = 0; $i<$count; $i++) {
			$this->assertSame($origData[$i], $loadedData[$i]);
			$this->assertEquals($origFiles[$i], $loadedFiles[$i]);
		}
		/*
		 * We can't read another byte since we should be at the end of the string.
		 */
		$this->expectException(\RuntimeException::class);
		$reader->getInt8();
	}
	
	public function testFromBinary(): void {
		$fg = new FileGroup();
		foreach(glob("/tmp/corviprotect/file*.bin") as $value) {
			$file = File::fromPath($value);
			$fg->addFile(FileTransportContainer::fromFile($file));
		}
		$binary = $fg->toBinary();
		$newFg = FileGroup::fromBinary($binary);
		$this->assertSame(array_sum($this->sizes), $newFg->getPayloadSize());
		$this->assertEquals($fg, $newFg);
	}
}