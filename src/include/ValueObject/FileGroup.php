<?php
use plibv4\Binary\StringWriter;
use plibv4\Binary\StringReader;
/**
 * Class to hold small file data and metadata, in order to transfer them in one
 * go.
 */
class FileGroup {
	private array $filedata = [];
	private array $file = [];
	private int $size = 0;
	function __construct() {
		;
	}
	
	function addFile(File $file): void {
		$size = $file->getSize();
		if($file->getType() === \Catalog::TYPE_FILE) {
			$filedata = file_get_contents($file->getPath());
			/*
			 * Throw FileChangedException, should file size have changed between 
			 * creating File object and getting file contents.
			 * The ba client can then retry.
			 */
			if($size != strlen($filedata)) {
				throw new FileChangedException("file changed while adding to FileGroup");
			}
		}
		/**
		 * For symlinks, the target path is stored within the 'data' part of
		 * the file.
		 */
		if($file->getType() === \Catalog::TYPE_LINK) {
			$filedata = $file->getTarget();
		}
		$this->file[] = $file;
		$this->filedata[] = $filedata;
		$this->size += $size;
	}
	
	function getFileCount(): int {
		return count($this->file);
	}
	
	function getFile(int $i): File {
		return $this->file[$i];
	}

	function getFileData(int $i): string {
		return $this->filedata[$i];
	}
	
	function getPayloadSize(): int {
		return $this->size;
	}
	
	function toBinary(): string {
		$binary = "";
		$writer = new StringWriter(StringWriter::LE);
		$writer->addUInt8(count($this->file));
		foreach($this->file as $key => $value) {
			$writer->addIndexedString(16, $value->toBinary());
			$writer->addIndexedString(32, $this->filedata[$key]);
		}
	return $writer->getBinary();
	}
	
	static function fromBinary(string $binary): FileGroup {
		$fg = new FileGroup();
		$reader = new StringReader($binary, StringReader::LE);
		$count = $reader->getUInt8();
		for($i = 0; $i<$count; $i++) {
			$fg->file[] = File::fromBinary($reader->getIndexedString(16));
			$filedata = $reader->getIndexedString(32);
			$fg->size += strlen($filedata);
			$fg->filedata[] = $filedata;
		}
	return $fg;
	}
	
}