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
	private $target = NULL;
	private $version = 1;
	const CREATE = 1;
	const UPDATE = 2;
	const DELETE = 3;
	private static $userCache = array();
	private static $groupCache = array();
	private function __construct() {
	}
	
	static function fromPath(string $path): File {
		$obj = new File();
		$obj->path = $path;
		$obj->reload();
	return $obj;
	}
	
	static function fromBinary(string $binary): File {
		$reader = new \plibv4\Binary\StringReader($binary, \plibv4\Binary\StringReader::LE);
		$file = new File();
		$file->version = $reader->getUInt8();
		$file->size = $reader->getUInt64();
		$file->atime = $reader->getUInt64();
		$file->ctime = $reader->getUInt64();
		$file->mtime = $reader->getUInt64();
		$file->permissions = $reader->getUInt16();
		$file->type = $reader->getUInt8();
		$file->owner = $reader->getIndexedString(8, 255);
		$file->group = $reader->getIndexedString(8, 255);
		$file->path = $reader->getIndexedString(16, 4096);
	return $file;
	}
	
	function toBinary(): string {
		$writer = new \plibv4\Binary\StringWriter(\plibv4\Binary\StringWriter::LE);
		$writer->addUInt8($this->version);
		$writer->addUInt64($this->size);
		$writer->addUInt64($this->atime);
		$writer->addUInt64($this->ctime);
		$writer->addUInt64($this->mtime);
		$writer->addUInt16($this->permissions);
		$writer->addUInt8($this->type);
		$writer->addIndexedString(8, $this->owner, 255);
		$writer->addIndexedString(8, $this->group, 255);
		$writer->addIndexedString(16, $this->path, 4096);
	return $writer->getBinary();
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
		if(is_file($this->path) && !is_link($this->path)) {
			$this->type = Catalog::TYPE_FILE;
			return;
		}
		if(is_dir($this->path)) {
			$this->type = Catalog::TYPE_DIR;
		}
		if(is_link($this->path)) {
			$this->type = Catalog::TYPE_LINK;
			$this->target = readlink($this->path);
			/*
			 * The size of a link is the length of the target path it is
			 * pointing to.
			 * This is not what you get when you use filesize(link), but the
			 * amount which will be used up in the backup storage, as a link
			 * is a file containing the link target.
			 */
			$this->size = strlen($this->target);
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
	
	function getTarget(): string {
		if($this->type!=3) {
			throw new Exception("file is not a link");
		}
	return $this->target;
	}
	
	function getParent(): File {
		if(!$this->hasParent()) {
			throw new RuntimeException("File ".$this->getPath()." has no parent.");
			
		}
	return File::fromPath($this->getDirname());
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