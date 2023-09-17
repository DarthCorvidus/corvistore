<?php
/*
 * Written by Bing/ChatGPT
 */
namespace Net;
class MockReceiver implements StreamReceiver {
	private $recvSize;
	private $data = "";
	private $startCalled = false;
	private $endCalled = false;
	private $cancelCalled = false;

	public function setRecvSize(int $size) {
		$this->recvSize = $size;
	}

	public function getRecvSize(): int {
		return $this->recvSize;
	}

	public function receiveData(string $data) {
		$this->data .= $data;
	}

	public function getRecvLeft(): int {
		return $this->recvSize - strlen($this->data);
	}

	public function onRecvStart() {
		$this->startCalled = true;
	}

	public function onRecvEnd() {
		$this->endCalled = true;
	}

	public function onRecvCancel() {
		$this->cancelCalled = true;
	}

	public function hasStarted(): bool {
		return $this->startCalled;
	}

	public function hasEnded(): bool {
		return $this->endCalled;
	}

	public function wasCancelled(): bool {
		return $this->cancelCalled;
	}

	public function getString(): string {
		return $this->data;
	}
}