<?php
namespace Regression;
class DirEntry {
	private $sha1;
	private $path;
	private $ctime;
	private $mode;
	function __construct(string $path) {
		$this->path = $path;
		$stat = stat($path);
		$this->ctime = $stat["ctime"];
		$this->mode = $stat["mode"];
		if(is_file($path)) {
			$this->sha1 = sha1_file($path);
		}
	}
	
	public function getChecksum(): string {
		return $this->sha1;
	}
	
	public function getCtime(): int {
		return $this->ctime;
	}

	public function getMode(): int {
		return $this->mode;
	}

}
