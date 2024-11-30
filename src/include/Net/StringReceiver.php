<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Net;
class StringReceiver implements StreamReceiver {
	private int $pos = 0;
	private string $string = "";
	private int $size = 0;
	private int $left = 0;
	public function __construct() {
		;
	}
	public function getRecvLeft(): int {
		return $this->left;
	}

	public function getRecvSize(): int {
		return $this->size;
	}

	public function onRecvCancel(): void {
		$this->pos = 0;
		$this->string = "";
	}

	public function onRecvEnd(): void {
		
	}

	public function onRecvStart(): void {
		$this->pos = 0;
		$this->string = "";
	}

	public function receiveData(string $data): void {
		$len = strlen($data);
		#if($len>=$this->left) {
		#	$this->string .= substr($data, 0, $this->left);
		#	$this->left = 0;
		#return;
		#}
		$this->string .= $data;
		$this->left = $this->left-$len;
	}

	public function setRecvSize(int $size): void {
		$this->size = $size;
		$this->left = $size;
	}
	
	public function getString(): string {
		return $this->string;
	}

}
