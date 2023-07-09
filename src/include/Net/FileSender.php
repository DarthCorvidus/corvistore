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
	public function __construct(\File $file) {
		$this->file = $file;
		$this->size = $file->getSize();
		$this->left = $this->size;
	}
	public function getSendData(int $amount): string {
		if(!is_resource($this->handle)) {
			throw new \RuntimeException("Resource for ".$this->file->getPath()." went away.");
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
		$this->file->reload();
	return $this->file->getSize();
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
	}

}
