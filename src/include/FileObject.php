<?php
declare(strict_types=1);
/**
 * FileObject
 * 
 * FileObject represents a file which is about to be saved or already saved in
 * Crow Protect. Every version of a file stored in Crow Protect is unique, with
 * one version being 'active' (the most current).
 * 
 * @author Claus-Christoph Küthe
 */
class FileObject {
	private $id;
	private $parentId;
	private $path;
	private $staging;
	private $ctime;
	private $atime;
	private $mtime;
	private $origSize;
	private $storageSize;
	private $permissions;
	private $owner;
	private $group;
	private $node;
	private $state = 0;
	private $active = 0;
	private $created;
	private function __construct() {
		
	}
	
	static function fromLocal($node, $path) {
		$fileObject = new FileObject();
		$fileObject->node = $node;
		$fileObject->path = realpath($path);
		$fileObject->staging = $fileObject->path;
		$stat = stat($path);
		$fileObject->ctime = $stat["ctime"];
		$fileObject->atime = $stat["atime"];
		$fileObject->mtime = $stat["mtime"];
		$fileObject->permissions = substr(sprintf('%o', fileperms($path)), -4);
		
		$owner = posix_getpwuid($stat["uid"]);
		$group = posix_getgrgid($stat["uid"]);
		$fileObject->owner = $owner["name"];
		$fileObject->group = $group["name"];
		$fileObject->origSize = filesize($path);
	return $fileObject;	
	}

	function getPath(): string {
		return $this->path;
	}
	
	function getStaging(): string {
		return $this->staging;
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
	
	function getOriginalSize(): int {
		return $this->origSize;
	}
}
