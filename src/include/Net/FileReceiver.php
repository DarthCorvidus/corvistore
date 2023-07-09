<?php
namespace Net;
/**
 * Description of FileReceiver
 *
 * @author hm
 */
class FileReceiver implements StreamReceiver {
	private $filename;
	private $handle;
	private $size;
	private $left;
	function __construct(string $filename) {
		$this->filename = $filename;
	}
	public function receiveData(string $data) {
		$len = strlen($data);
		$diff = $this->left - $len;
		if($diff < 0) {
			throw new \RuntimeException("expected filesize exceeded by ".abs($diff)." bytes");
		}
		$written = fwrite($this->handle, $data);
		$this->left = $this->left - $written;
		
	}

	public function getRecvLeft(): int {
		return $this->left;
	}

	public function setRecvSize(int $size) {
		$this->size = $size;
	}
	
	public function getRecvSize(): int {
		return $this->size;
	}

	public function onRecvCancel() {
		fclose($this->handle);
		unlink($this->handle);
	}

	public function onRecvEnd() {
		fclose($this->handle);
	}

	public function onRecvStart() {
		$this->left = $this->size;
		$this->handle = fopen($this->filename, "w");
	}

}
