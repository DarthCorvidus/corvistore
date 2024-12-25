<?php
namespace Net;
/**
 * SafeReceiver
 * 
 * SafeReceiver is the counterpart of SafeSender, which evaluates control blocks
 * as send by the SafeSender and forwards it to an inner stream receiver.
 * If it receives a cancel block, it can act accordingly it by calling
 * onCancel on the inner receiver.
 *
 * @author hm
 */
class SafeReceiver implements StreamReceiver {
	private StreamReceiver $receiver;
	private int $increment = 0;
	private int $left;
	private int $size;
	private int $blocksize;
	function __construct(\Net\StreamReceiver $receiver, int $blocksize) {
		$this->receiver = $receiver;
		// Length is at least blocksize * 2: the first and the last control block.
		$this->size = $blocksize*2;
		$this->left = $blocksize*2;
		$this->blocksize = $blocksize;
	}
	public function receiveData(string $data): void {
		if($this->increment === 0) {
			$type = ord($data[0]);
			// This should not happen, as SafeReceiver should only be called when $type is Protocol::FILE.
			if($type !== Protocol::FILE) {
				throw new \RuntimeException("first block shows invalid type, ".Protocol::FILE." expected, got ".$type);
			}
			$this->size = \IntVal::uint64LE()->getValue(substr($data, 1, 8));
			$this->left = $this->size - $this->blocksize;
			$this->receiver->setRecvSize(\IntVal::uint64LE()->getValue(substr($data, 9, 8)));
			$this->increment++;
			//Initialize receiver
			$this->receiver->onRecvStart();
		return;
		}
		if($this->left == $this->blocksize) {
			$this->left -= $this->blocksize;
			$status = \Net\Protocol::determineControlBlock($data);
			$this->increment = 0;
			if($status== \Net\Protocol::FILE_OK) {
				$this->receiver->onRecvEnd();
			}
			if($status== \Net\Protocol::FILE_CANCEL) {
				$this->receiver->onRecvCancel();
			}
		return;
		}
		/*
		 * Truncate the last block of the payload if it is smaller than one
		 * block.
		 */
		if($this->receiver->getRecvLeft()<=$this->blocksize) {
			$left = $this->receiver->getRecvLeft();
			$this->receiver->receiveData(substr($data, 0, $left));
			$this->increment++;
			$this->left -= $this->blocksize;
		return;
		}
		$this->increment++;
		$this->receiver->receiveData($data);
		$this->left -= $this->blocksize;
	}

	public function getRecvLeft(): int {
		return $this->left;
	}

	public function setRecvSize(int $size): void {
		throw new \RuntimeException("Size is determined from the first data block, do not set manually.");
	}
	
	public function getRecvSize(): int {
		return $this->size;
	}

	public function onRecvCancel(): void {
		$this->receiver->onRecvCancel();
	}

	public function onRecvEnd(): void {
		$this->receiver->onRecvEnd();
	}

	public function onRecvStart(): void {
		$this->receiver->onRecvStart();
	}

}
