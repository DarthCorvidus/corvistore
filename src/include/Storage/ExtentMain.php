<?php
namespace Storage;
class ExtentMain {
	private $version;
	private $uuid;
	private $path;
	private $node;
	private $mtime;
	private $owner;
	private $group;
	private $totalSize;
	private $partSize;
	private $perms;
	private $storetime;
	private $part;
	private $type;
	private function __construct() {
		;
	}
	
	static function fromFile(\File $file, string $node): ExtentMain {
		#\Assert::isEnum($blocksize, array(1024, 2048, 4096, 8192));
		$inst = new ExtentMain();
		$inst->version = 1;
		$inst->totalSize = $file->getSize();
		$inst->mtime = $file->getMTime();
		$inst->node = $node;
		$inst->owner = $file->getOwner();
		$inst->group = $file->getGroup();
		$inst->part = 0;
		$inst->partSize = $inst->totalSize;
		$inst->path = $file->getPath();
		$inst->perms = $file->getPerms();
		$inst->type = $file->getType();
		$inst->storetime = time();
		$inst->uuid = sha1($inst->path.$inst->owner.$inst->group.$inst->node.$inst->mtime, TRUE);
	return $inst;
	}
	
	function getTotalSize(): int {
		return $this->totalSize;
	}

	function getVersion(): int {
		return $this->version;
	}
	
	function getMtime(): int {
		return $this->mtime;
	}
	
	function getNode(): string {
		return $this->node;
	}
	
	function getOwner(): string {
		return $this->owner;
	}
	
	function getGroup(): string {
		return $this->group;
	}
	
	function getPartSize(): int {
		return $this->partSize;
	}
	
	function getPath(): int {
		return $this->path;
	}
	
	function getPerms(): int {
		return $this->perms;
	}
	
	function getType(): int {
		return $this->type;
	}
	
	function getStoretime(): int {
		return $this->storetime;
	}
	
	function getUUID(): string {
		return $this->uuid;
	}
		
	
	function toBinary(): string {
		$string = chr($this->version);
		$string .= \IntVal::uint64LE()->putValue($this->totalSize);
		$string .= \IntVal::uint64LE()->putValue($this->partSize);
		$string .= \IntVal::uint32LE()->putValue($this->storetime);
		$string .= \IntVal::uint32LE()->putValue($this->mtime);
		$string .= \IntVal::uint16LE()->putValue($this->perms);
		$string .= chr($this->type);
			
		$string .= \IntVal::uint16LE()->putValue(strlen($this->path));
		$string .= str_pad($this->path, 4096, chr(0));
		
		$string .= chr(strlen($this->owner));
		$string .= str_pad($this->owner, 255, chr(0));
		
		$string .= chr(strlen($this->group));
		$string .= str_pad($this->group, 255, chr(0));
		
		$string .= chr(strlen($this->node));
		$string .= str_pad($this->node, 255, chr(0));
		
		$string .= sha1($this->path.$this->owner.$this->group.$this->node.$this->mtime, TRUE);
	return $string;
	}
	
	static function fromBinary(string $binary): ExtentMain {
		/*
		 * Using the StringReader here is quite nice, as it saves a lot of
		 * substr($binary, $pos, $x) work.
		 */
		$reader = new \Net\StringSender(1, $binary);
		$inst = new ExtentMain();
		$inst->part = 0;
		$inst->version = ord($reader->getSendData(1));
		$inst->totalSize = \IntVal::uint64LE()->getValue($reader->getSendData(8));
		$inst->partSize = \IntVal::uint64LE()->getValue($reader->getSendData(8));
		$inst->storetime = \IntVal::uint32LE()->getValue($reader->getSendData(4));
		$inst->mtime = \IntVal::uint32LE()->getValue($reader->getSendData(4));
		$inst->perms = \IntVal::uint16LE()->getValue($reader->getSendData(2));
		$inst->type = ord($reader->getSendData(1));
		
		$strlen = \IntVal::uint16LE()->getValue($reader->getSendData(2));
		$inst->path = substr($reader->getSendData(4096), 0, $strlen);
		$strlen = ord($reader->getSendData(1));
		$inst->owner = substr($reader->getSendData(255), 0, $strlen);
		$strlen = ord($reader->getSendData(1));
		$inst->group = substr($reader->getSendData(255), 0, $strlen);
		$strlen = ord($reader->getSendData(1));
		$inst->node = substr($reader->getSendData(255), 0, $strlen);
		$inst->uuid = $reader->getSendData(20);
	return $inst;
	}
}
