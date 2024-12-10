<?php
namespace Storage;
/**
 * This is a sort of helper class to get rid of a plethora of possible null
 * values in StorageBasic itself, which is caused by the fact that StorageBasic
 * has to be primed to receive data.
 * 
 * The design of Storage/StorageBasic/StorageBasicContext is probably not suited
 * to be a role model in software architecture, but I leave it for now. When
 * other storage methods are added, it might become clearer on where the
 * dividing lines should be according to "separation of concerns".
 */

class StorageBasicContext {
	private \VersionEntry $versionEntry;
	private \Partition $partition;
	private \File $file;
	private mixed $writeHandle;
	private ?int $storeId = null;
	private int $size;
	private int $left;
	function __construct(\File $file, \Partition $partition, \VersionEntry $versionEntry) {
		$this->file = $file;
		$this->partition = $partition;
		$this->versionEntry = $versionEntry;
		$this->size = $this->file->getSize();
		$this->left = $this->size;
	}
	
	public function getFile(): \File {
		return $this->file;
	}
	
	public function getPartition(): \Partition {
		return $this->partition;
	}
	
	public function getVersionEntry(): \VersionEntry {
		return $this->versionEntry;
	}
	
	public function setStoreId(int $id): void {
		$this->storeId = $id;
	}
	
	public function getStoreId(): int {
		if($this->storeId === null) {
			throw new \RuntimeException("no store id set");
		}
		return $this->storeId;
	}
	
	public function getLeft(): int {
		return $this->left;
	}
	
	public function subtractLeft(int $chunk): void {
		$this->left -= $chunk;
	}
	
	public function getSize(): int {
		return $this->size;
	}
}