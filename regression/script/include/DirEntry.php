<?php
namespace Regression;
class DirEntry {
	private $sha1 = "";
	private $file;
	function __construct(string $path) {
		$this->file = \File::fromPath($path);
		if($this->file->getType() === \Catalog::TYPE_FILE) {
			$this->sha1 = sha1_file($path);
		}
	}

	public function getFile(): \File {
		return $this->file;
	}
	
	public function getChecksum(): string {
		return $this->sha1;
	}
	
	public function getMtime(): int {
		return $this->file->getMTime();
	}

	public function getMode(): int {
		return $this->file->getPerms();
	}
}
