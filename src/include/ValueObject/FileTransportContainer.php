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
		if($file->getType() === \Catalog::TYPE_LINK) {
			// Links are stored as data on the backup server
			$this->data = $file->getTarget();
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
		$bw->addString16($this->meta->toBinary());
		$bw->addString32($this->data);
	return $bw->getBinary();
	}

	public static function fromBinary(string $binary): FileTransportContainer {
		$new = new FileTransportContainer();
		$br = new StringReader($binary, StringReader::LE);
		$meta = \File::fromBinary($br->getString16());
		$data = $br->getString32();
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
