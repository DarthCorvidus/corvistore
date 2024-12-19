<?php

use plibv4\Binary\StringWriter;
use plibv4\Binary\StringReader;

/**
 * FileTransportContainer
 * 
 * This class is a container for a file and its data. It is used to transport small files en masse.
 */

class FileTransportContainer implements \BinaryPersistable {
	/** @psalm-suppress PropertyNotSetInConstructor */
	private \File $meta;
	private string $data = "";

	private function __construct() {
		
	}

	private function loadData(\File $file): void {
		if ($file->getSize() === 0) {
			return;
		}
		$data = @file_get_contents($file->getPath());
		if ($data === FALSE) {
			throw new \RuntimeException("File '".$file->getPath()."' vanished before transfer");
		}
		/**
		 * We could reload File, but then the file may change again and so on; the idea is that the
		 * backup process as such may try several times when catching FileChangedException.
		 */
		if(strlen($data) !== $file->getSize()) {
			throw new \FileChangedException("File '".$file->getPath()."' changed size during transfer");
		}
		$this->data = $data;
	}

	public static function fromFile(\File $file): FileTransportContainer {
		$new = new FileTransportContainer();
		$new->meta = $file;
		$new->loadData($file);
	return $new;
	}

	public function getFile(): \File {
		return $this->meta;
	}

	public function getData(): string {
		return $this->data;
	}

	public function toBinary(): string {
		$bw = new StringWriter(StringWriter::LE);
		$bw->addIndexedString(16, $this->meta->toBinary());
		$bw->addIndexedString(32, $this->data);
	return $bw->getBinary();
	}

	public static function fromBinary(string $binary): FileTransportContainer {
		$new = new FileTransportContainer();
		$br = new StringReader($binary, StringReader::LE);
		$meta = \File::fromBinary($br->getIndexedString(16));
		$data = $br->getIndexedString(32);
		$new->meta = $meta;
		$new->data = $data;
	return $new;
	}

	public static function fromStoredFile(string $data): FileTransportContainer {
		$new = new FileTransportContainer();
		$header = substr($data, 0, 8192);
		$meta = \File::fromBinary($header);
		$new->meta = $meta;
		if ($meta->getSize() !== 0) {
			$new->data = substr($data, 8192);
		}
	return $new;
	}
}
