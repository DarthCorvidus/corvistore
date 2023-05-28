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
	private static $userCache = array();
	private static $groupCache = array();
	function __construct(string $path) {
		// removed realpath() here, as I don't see how it could be called with a
		// relative path, apart from any initial path supplied by pesky users,
		// on which realpath() can be called once.
		$this->path = $path;
		$stat = stat($this->path);
		$this->ctime = $stat["ctime"];
		$this->atime = $stat["atime"];
		$this->mtime = $stat["mtime"];
		$this->permissions = fileperms($path);
		// group & user names are cached
		$this->owner = $this->getOwnerName($stat["uid"]);
		$this->group = $this->getGroupName($stat["gid"]); 
		$this->size = filesize($path);
		$this->type = Catalog::TYPE_OTHER;
		//usually, most files are files, so check for file first and end early.
		if(is_file($this->path)) {
			$this->type = Catalog::TYPE_FILE;
			return;
		}
		if(is_dir($this->path)) {
			$this->type = Catalog::TYPE_DIR;
		}
	}
	
	private function getOwnerName(int $uid) {
		if(isset(self::$userCache[$uid])) {
			return self::$userCache[$uid];
		}
		$owner = posix_getpwuid($uid);
		self::$userCache[$uid] = $owner["name"];
	return $owner["name"];
	}
	
	private function getGroupName(int $gid) {
		if(isset(self::$groupCache[$gid])) {
			return self::$groupCache[$gid];
		}
		$group = posix_getgrgid($gid);
		self::$groupCache[$gid] = $group["name"];
	return $group["name"];
	}
	
	
	function isEqual(CatalogEntry $entry) {
		
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