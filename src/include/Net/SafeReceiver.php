<?php
namespace Net;
/**
 * Description of FileReceiver
 *
 * @author hm
 */
class SafeReceiver implements StreamReceiver {
	private $receiver;
	private $increment = 0;
	private $left;
	private $size;
	private $bitsize;
	private $blocksize;
	function __construct(\Net\StreamReceiver $receiver, int $blocksize) {
		$this->receiver = $receiver;
		// Length is at least blocksize * 2: the first and the last control block.
		$this->size = $blocksize*2;
		$this->left = $blocksize*2;
		$this->bitsize = log($blocksize, 2);
		$this->blocksize = $blocksize;
	}
	public function receiveData(string $data) {
		if($this->increment==0) {
			$type = ord($data[0]);
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

	public function setRecvSize(int $size) {
		throw new \RuntimeException("Size is determined from the first data block, do not set manually.");
		/**
		 * As the receiver is reused in ProtocolAsync, we need to reset the
		 * increment here.
		 * Glad I had a unit test for this one, otherwise I would have noticed
		 * it by having a corrupted backup.
		 */
		$this->increment = 1;
		$this->receiver->setRecvSize($size);
	}
	
	public function getRecvSize(): int {
		return $this->size;
	}

	public function onRecvCancel() {
		$this->receiver->onRecvCancel();
	}

	public function onRecvEnd() {
		$this->receiver->onRecvEnd();
	}

	public function onRecvStart() {
		$this->receiver->onRecvStart();
	}

}
