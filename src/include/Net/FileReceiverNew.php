<?php
namespace Net;
/**
 * This is a temporary stand in for FileReceiver, which takes a file's path name
 * from the file header that is stored on the server when it receives a file,
 * which unfortunately adds an overhead of 8KiB per file.
 * Link restore does not look very nice and is somewhat inefficient, however,
 * this is an intentional quick'n'dirty solution.
 *
 * @author hm
 */
class FileReceiverNew implements StreamReceiver {
	private string $filename = "";
	private mixed $handle = null;
	private int $size = 0;
	private int $left = 0;
	private string $header = "";
	private int $blockCount = 0;
	/** @psalm-suppress PropertyNotSetInConstructor */
	private \File $meta;
	private string $relativePath;
	private string $link = "";
	function __construct(string $relativePath, bool $replace = false) {
		$this->relativePath = $relativePath;
	}
	
	public function receiveData(string $data): void {
		if($this->blockCount<8) {
			$this->header .= $data;
			$this->blockCount++;
			if($this->blockCount === 8) {
				$this->meta = \File::fromBinary($this->header);
				$this->filename = $this->relativePath."".$this->meta->getPath();
				if($this->meta->getType() === \Catalog::TYPE_FILE) {
					$this->handle = fopen($this->filename, "w");
				}
				if($this->meta->getType() === \Catalog::TYPE_LINK) {
					$this->link = "";
				}
				
			}
			$this->left -= strlen($data);
		return;
		}
		if($this->meta->getType() === \Catalog::TYPE_LINK) {
			$this->left -= strlen($data);
			$this->link .= $data;
		return;
		}
		
		if(!is_resource($this->handle)) {
			throw new \RuntimeException("Resource for ".$this->filename." not available.");
		}
		$len = strlen($data);
		$diff = $this->left - $len;
		if($diff < 0) {
			throw new \RuntimeException("expected filesize ".$this->size." exceeded by ".abs($diff)." bytes");
		}
		if($len>=$this->left) {
			fwrite($this->handle, substr($data, 0, $this->left));
			$this->left = 0;
		return;
		}

		$written = fwrite($this->handle, $data);
		$this->left = $this->left - $written;
		
	}

	public function getRecvLeft(): int {
		return $this->left;
	}

	public function setRecvSize(int $size): void {
		$this->size = $size;
		$this->left = $size;
		$this->header = "";
		$this->blockCount = 0;
		$this->link = "";
	}
	
	public function getRecvSize(): int {
		return $this->size;
	}

	public function onRecvCancel(): void {
		fclose($this->handle);
		unlink($this->filename);
	}

	
	
	public function onRecvEnd(): void {
		if($this->meta->getType() === \Catalog::TYPE_LINK) {
			/**
			 * It seems that touch would set the mtime of the link target, so
			 * we just recreate a missing link here.
			 */
			symlink($this->link, $this->filename);
			$this->link = "";
			return;
		}
		fclose($this->handle);
		chown($this->filename, $this->meta->getOwner());
		chgrp($this->filename, $this->meta->getGroup());
		/*
		 * This is one of the very rare occurrences I found a bug in PHP:
		 * chown and chgrp reset any sticky bit, so we have to call chmod
		 * last.
		 */
		chmod($this->filename, $this->meta->getPerms());
		touch($this->filename, $this->meta->getMtime());
		
	}

	public function onRecvStart(): void {
		#$this->handle = fopen($this->filename, "w");
	}

}
