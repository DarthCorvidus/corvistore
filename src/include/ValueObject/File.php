<?php
class File {
	private $path;
	private $ctime;
	private $atime;
	private $mtime;
	private $size;
	private $permissions;
	private $owner;
	private $group;
	private $type;
	function __construct(string $path) {
		$this->path = realpath($path);
		$stat = stat($this->path);
		$this->ctime = $stat["ctime"];
		$this->atime = $stat["atime"];
		$this->mtime = $stat["mtime"];
		$this->permissions = fileperms($path);
		
		$owner = posix_getpwuid($stat["uid"]);
		$group = posix_getgrgid($stat["gid"]);
		$this->owner = $owner["name"];
		$this->group = $group["name"];
		$this->size = filesize($path);
		$this->type = Catalog::TYPE_OTHER;
		if(is_dir($this->path)) {
			$this->type = Catalog::TYPE_DIR;
		}
		if(is_file($this->path)) {
			$this->type = Catalog::TYPE_FILE;
		}
	}
	
	function getPath(): string {
		return $this->path;
	}
	
	function getBasename(): string {
		return basename($this->path);
	}
	
	function hasParent(): bool {
		if($this->getDirname()=="/") {
			return FALSE;
		}
	return TRUE;
	}
	
	function getParent(): File {
		if(!$this->hasParent()) {
			throw new RuntimeException("File ".$this->getPath()." has no parent.");
			
		}
	return new File($this->getDirname());
	}
	
	function getDirname() {
		return dirname($this->path);
	}
	
	function getATime(): int {
		return $this->atime;
	}
	
	function getCTime(): int {
		return $this->ctime;
	}
	
	function getMTime(): int {
		return $this->mtime;
	}
	
	function getPerms(): int {
		return $this->permissions;
	}
	
	function getOwner(): string {
		return $this->owner;
	}
	
	function getGroup(): string {
		return $this->group;
	}
	
	function getSize(): int {
		return $this->size;
	}
	
	function getType(): int {
		return $this->type;
	}

}