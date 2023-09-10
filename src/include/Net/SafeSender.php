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
class SafeSender implements StreamSender {
	private $sender;
	private $increment = 1;
	public function __construct(\Net\StreamSender $sender) {
		$this->sender = $sender;
	}
	
	public function getSendType(): int {
		return $this->sender->getSendType();
	}
	
	public function getSendData(int $amount): string {
		if($this->increment % 10 == 0) {
			$this->increment++;
		return Protocol::getControlBlock(Protocol::FILE_OK, $amount);
		}
		$this->increment++;
		$read = $this->sender->getSendData($amount);
	return $read;
	}

	public function getSendLeft(): int {
		return $this->sender->getSendLeft();
	}

	public function getSendSize(): int {
		return $this->sender->getSendSize();
	}

	public function onSendCancel() {
		$this->sender->onSendCancel();
	}

	public function onSendEnd() {
		$this->sender->onSendEnd();
	}

	public function onSendStart() {
		$this->sender->onSendStart();
	}

}
