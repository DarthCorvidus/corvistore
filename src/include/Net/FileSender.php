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
	public function __construct(\File $file) {
		$this->file = $file;
	}
	public function getData(int $amount): string {
		if(!is_resource($this->handle)) {
			throw new \RuntimeException("Resource for ".$this->file->getPath()." went away.");
		}
		$read = fread($this->handle, $amount);
		$len = strlen($read);
		if($len!=$amount) {
			throw new \RuntimeException("Unable to read expected amount from ".$this->file->getPath().", expected ".$amount.", got ".$len);
		}
	return $read;
	}

	public function getLeft(): int {
		
	}

	public function getSize(): int {
		$this->file->reload();
	return $this->file->getSize();
	}

	public function onCancel() {
		
	}

	public function onEnd() {
		fclose($this->handle);
	}

	public function onStart() {
		$this->handle = @fopen($this->file->getPath(), "r");
		if($this->handle===FALSE) {
			throw new \RuntimeException("unable to open ".$this->file->getPath()." for read.");
		}
	}

}
