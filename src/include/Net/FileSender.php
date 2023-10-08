<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Net;

/**
 * Description of FileSender
 *
 * @author hm
 */
class FileSender implements StreamSender {
	private $file;
	private $handle;
	private $size;
	private $left;
	private $type;
	private $offset;
	private $started = FALSE;
	public function __construct(\File $file, int $offset = 0) {
		$this->file = $file;
		$this->size = $file->getSize()-$offset;
		$this->left = $this->size;
		$this->type = $file->getType();
		$this->offset = $offset;
	}
	
	public function getSendType(): int {
		return ProtocolAsync::FILE;
	}
	
	public function getSendData(int $amount): string {
		if(!is_resource($this->handle)) {
			throw new \RuntimeException("Resource for ".$this->file->getPath()." went away.");
		}
		if(!file_exists($this->file->getPath())) {
			throw new \RuntimeException("File went away during transfer.");
		}
		if(filesize($this->file->getPath())-$this->offset!=$this->size) {
			throw new \RuntimeException("Filesize has changed during transfer");
		}
		if($amount===0) {
			return "";
		}
		$read = fread($this->handle, $amount);
		$len = strlen($read);
		if($len!=$amount) {
			throw new \RuntimeException("Unable to read expected amount from ".$this->file->getPath().", expected ".$amount.", got ".$len);
		}
		$this->left = $this->left - $amount;
	return $read;
	}

	public function getSendLeft(): int {
		return $this->left;
	}

	public function getSendSize(): int {
		return $this->size;
	}

	public function onSendCancel() {
		fclose($this->handle);
	}

	public function onSendEnd() {
		fclose($this->handle);
	}

	public function onSendStart() {
		$this->handle = @fopen($this->file->getPath(), "r");
		if($this->handle===FALSE) {
			throw new \RuntimeException("unable to open ".$this->file->getPath()." for read.");
		}
		fseek($this->handle, $this->offset);
		$this->started = true;
	}

}
