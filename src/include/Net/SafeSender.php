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
	private $payloadSize;
	private $increment = 0;
	private $size;
	private $blocksize;
	private $cancelled = FALSE;
	public function __construct(\Net\StreamSender $sender, int $blocksize) {
		$this->sender = $sender;
		/*
		 * I think that this is the first time since 1997 that I used log(). I
		 * don't want to know how many CPU cycles this eats up, so I'll either
		 * use some kind of hashmap at some point or use the exponent as a
		 * unit for block sizes altogether; arbitrary sizes like 324 make no
		 * sense anyway.
		 */
		$exponent = (int)log(1024, 2);
		$this->size = \Net\Protocol::ceilBlock($sender->getSendSize(), $exponent)+($blocksize*2);
		$this->payloadSize = $sender->getSendSize();
		$this->left = $this->size;
		$this->blocksize = $blocksize;
	}
	
	public function getSendType(): int {
		return $this->sender->getSendType();
	}
	
	public function getSendData(int $amount): string {
		// First block: Type file, SafeSender length, StreamSender length, padded to blocksize.
		if($this->increment == 0) {
			// call onSendStart() right at the beginning. If something goes wrong,
			// we can change the amount of the payload size immediately to 0, as
			// there is nothing to send.
			try {
				$this->sender->onSendStart();
				$header = chr(\Net\Protocol::FILE);
				$header .= \IntVal::uint64LE()->putValue($this->size);
				$header .= \IntVal::uint64LE()->putValue($this->sender->getSendSize());
			} catch (\RuntimeException $e) {
				$this->cancelled = TRUE;
				$header = chr(\Net\Protocol::FILE);
				$this->size = 2048;
				$this->left = 2048;
				$header .= \IntVal::uint64LE()->putValue($this->blocksize*2);
				$header .= \IntVal::uint64LE()->putValue(0);
			}
			$this->increment++;
			$this->left -= $amount;
		return \Net\Protocol::padRandom($header, $this->blocksize);
		}
		/*
		 * If the state is cancelled and the increment is one, we directly send
		 * a control block and no payload, because we sent the other side a
		 * payload length of zero.
		 */
		if($this->cancelled===TRUE && $this->increment == 1) {
			$this->left -= $amount;
			return \Net\Protocol::getControlBlock(\Net\Protocol::FILE_CANCEL, $this->blocksize);
		}
		#if($this->increment % 10 == 0) {
		#	$this->increment++;
		#return Protocol::getControlBlock(Protocol::FILE_OK, $amount);
		#}
		$this->increment++;
		// Regular block/block sized data from $this->sender. 
		if($this->sender->getSendLeft()>$this->blocksize) {
			$read = $this->sender->getSendData($amount);
			$this->left -= $amount;
		return $read;
		}
		// last block of payload.
		$rest = $this->sender->getSendLeft();
		if($rest!==0) {
			$this->left -= $amount;
			$read = $this->sender->getSendData($rest);
			$this->sender->onSendEnd();
		return \Net\Protocol::padRandom($read, $this->blocksize);
		}
		// send last control block
		$this->left -= $amount;
	return \Net\Protocol::getControlBlock(\Net\Protocol::FILE_OK, $this->blocksize);
	}

	public function getSendLeft(): int {
		return $this->left;
	}

	public function getSendSize(): int {
		return $this->size;
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
