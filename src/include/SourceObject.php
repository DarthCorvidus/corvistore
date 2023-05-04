<?php
declare(strict_types=1);
/**
 * SourceFile
 * 
 * SourceFile represents a file as it exists on the source file system.
 * 
 * @author Claus-Christoph Küthe
 */
class SourceObject {
	private $path;
	private $ctime;
	private $atime;
	private $mtime;
	private $size;
	private $permissions;
	private $owner;
	private $group;
	private $node;
	private $type;
	function __construct(Node $node, string $path) {
		$this->node = $node;
		$this->path = realpath($path);
		$stat = stat($this->path);
		$this->ctime = $stat["ctime"];
		$this->atime = $stat["atime"];
		$this->mtime = $stat["mtime"];
		$this->permissions = substr(sprintf('%o', fileperms($path)), -4);
		
		$owner = posix_getpwuid($stat["uid"]);
		$group = posix_getgrgid($stat["uid"]);
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
	
	function parentIsRoot() {
		if($this->getDirname()=="/") {
			return TRUE;
		}
	return FALSE;
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
	
	function getPerms(): string {
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
	
	function getNode(): Node {
		return $this->node;
	}
}
