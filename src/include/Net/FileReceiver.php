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
		if(file_exists($filename)) {
			throw new \InvalidArgumentException("file ".$filename." already exists.");
		}
		if(!is_dir(dirname($filename))) {
			throw new \InvalidArgumentException("target directory ".dirname($filename)." does not exist.");
		}
		$this->filename = $filename;
	}
	public function receiveData(string $data) {
		if(!is_resource($this->handle)) {
			throw new \RuntimeException("Resource for ".$this->filename." not available.");
		}
		#$len = strlen($data);
		#$diff = $this->left - $len;
		#if($diff < 0) {
		#	throw new \RuntimeException("expected filesize ".$this->size." exceeded by ".abs($diff)." bytes");
		#}
		#if($len>=$this->left) {
		#	fwrite($this->handle, substr($data, 0, $this->left));
		#	$this->left = 0;
		#return;
		#}

		$written = fwrite($this->handle, $data);
		$this->left = $this->left - $written;
		
	}

	public function getRecvLeft(): int {
		return $this->left;
	}

	public function setRecvSize(int $size) {
		$this->size = $size;
		$this->left = $size;
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
		$this->handle = fopen($this->filename, "w");
	}

}
