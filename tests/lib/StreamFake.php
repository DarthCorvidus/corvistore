<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of StreamFake
 *
 * @author hm
 */
class StreamFake implements \Net\Stream {
	private $data;
	private $pos;
	function __construct($data) {
		$this->data = $data;
		$this->pos = 0;
	}
	
	function read(int $amount): string {
		$data = substr($this->data, $this->pos, $amount);
		$this->pos += $amount;
	return $data;
	}

	public function close() {
		
	}

	public function write(string $data) {
		$this->data .= $data;
	}
	
	public function getData(): string {
		return $this->data;
	}

}
