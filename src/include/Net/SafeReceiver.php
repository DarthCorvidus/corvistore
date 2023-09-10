<?php
namespace Net;
/**
 * Description of FileReceiver
 *
 * @author hm
 */
class SafeReceiver implements StreamReceiver {
	private $receiver;
	private $increment = 1;
	function __construct(\Net\StreamReceiver $receiver) {
		$this->receiver = $receiver;
	}
	public function receiveData(string $data) {
		if($this->increment % 10 == 0) {
			$this->increment++;
			return;
		}
		$this->increment++;
		$this->receiver->receiveData($data);
	}

	public function getRecvLeft(): int {
		return $this->receiver->getRecvLeft();
	}

	public function setRecvSize(int $size) {
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
		return $this->receiver->getRecvSize();
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
