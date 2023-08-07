<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Net;
class StringReceiver implements StreamReceiver {
	private $pos;
	private $string;
	private $size;
	private $left;
	public function getRecvLeft(): int {
		return $this->left;
	}

	public function getRecvSize(): int {
		return $this->size;
	}

	public function onRecvCancel() {
		
	}

	public function onRecvEnd() {
		
	}

	public function onRecvStart() {
		
	}

	public function receiveData(string $data) {
		$len = strlen($data);
		if($len>=$this->left) {
			$this->string .= substr($data, 0, $this->left);
			$this->left = 0;
		return;
		}
		$this->string .= $data;
		$this->left = $this->left-$len;
	}

	public function setRecvSize(int $size) {
		$this->size = $size;
		$this->left = $size;
	}

}
