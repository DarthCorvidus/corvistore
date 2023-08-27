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
	private $action = NULL;
	const CREATE = 1;
	const UPDATE = 2;
	const DELETE = 3;
	private static $userCache = array();
	private static $groupCache = array();
	function __construct(string $path) {
		// removed realpath() here, as I don't see how it could be called with a
		// relative path, apart from any initial path supplied by pesky users,
		// on which realpath() can be called once.
		$this->path = $path;
		$this->reload();
	}
	
	function setAction(int $action) {
		Assert::isClassConstant(self::class, $action);
		$this->action = $action;
	}
	
	function getAction(): int {
		return $this->action;
	}
	
	function reload() {
		clearstatcache();
		$stat = @stat($this->path);
		if($stat===FALSE) {
			throw new Exception("unable to stat ".$this->path);
		}
		$this->ctime = $stat["ctime"];
		$this->atime = $stat["atime"];
		$this->mtime = $stat["mtime"];
		$this->permissions = fileperms($this->path);
		// group & user names are cached
		$this->owner = $this->getOwnerName($stat["uid"]);
		$this->group = $this->getGroupName($stat["gid"]); 
		$this->size = filesize($this->path);
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
	
	
	function isEqual(CatalogEntry $entry): bool {
		//not equal, if type is not equal (quite obvious)
		$latest = $entry->getVersions()->getLatest();
		if($this->getType()!=$latest->getType()) {
			return FALSE;
		}
		// A file has changed if the mtime is different (actually, this is not
		// certain, but the only alternative would be to calculate a checksum)
		if($this->getType()==Catalog::TYPE_FILE && $this->getMTime()!=$latest->getMtime()) {
			return false;
		}
		// A file has changed if the size is different.
		if($this->getType()==Catalog::TYPE_FILE && $this->getSize()!=$latest->getSize()) {
			return false;
		}
	return TRUE;
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