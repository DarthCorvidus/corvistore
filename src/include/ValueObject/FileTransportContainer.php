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

	public static function fromFile(\File $file): FileTransportContainer {
		$new = new FileTransportContainer();
		$new->meta = $file;
		if ($file->getSize() !== 0) {
			$new->data = file_get_contents($file->getPath());
		}
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
