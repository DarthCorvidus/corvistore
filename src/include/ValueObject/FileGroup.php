<?php
use plibv4\Binary\StringWriter;
use plibv4\Binary\StringReader;
/**
 * Class to hold small file data and metadata, in order to transfer them in one
 * go.
 */
class FileGroup implements BinaryPersistable {
	private int $size = 0;
	/** @var list<\FileTransportContainer> */
	private array $container = [];
	function __construct() {
		;
	}
	
	function addFile(\FileTransportContainer $container): void {
		$filedata = "";
		$size = $container->getFile()->getSize();
		$this->container[] = $container;
		$this->size += $size;
	}
	
	function getFileCount(): int {
		return count($this->container);
	}
	
	function getFile(int $i): File {
		return $this->container[$i]->getFile();
	}

	function getFileData(int $i): string {
		return $this->container[$i]->getData();
	}
	
	function getPayloadSize(): int {
		return $this->size;
	}
	
	function toBinary(): string {
		$writer = new StringWriter(StringWriter::LE);
		$writer->addUInt8(count($this->container));
		foreach($this->container as $key => $value) {
			$writer->addIndexedString(32, $value->toBinary());
		}
	return $writer->getBinary();
	}
	
	static function fromBinary(string $binary): FileGroup {
		$fg = new FileGroup();
		$reader = new StringReader($binary, StringReader::LE);
		$count = $reader->getUInt8();
		for($i = 0; $i<$count; $i++) {
			$container = FileTransportContainer::fromBinary($reader->getIndexedString(32));
			$fg->container[] = $container;
			$fg->size += $container->getFile()->getSize();
		}
	return $fg;
	}
	
}