<?php
/*
 * Written by Bing/ChatGPT
 */
namespace Net;
class MockReceiver implements StreamReceiver {
	private int $recvSize = 0;
	private string $data = "";
	private bool $startCalled = false;
	private bool $endCalled = false;
	private bool $cancelCalled = false;

	public function setRecvSize(int $size): void {
		$this->recvSize = $size;
	}

	public function getRecvSize(): int {
		return $this->recvSize;
	}

	public function receiveData(string $data): void {
		$this->data .= $data;
	}

	public function getRecvLeft(): int {
		return $this->recvSize - strlen($this->data);
	}

	public function onRecvStart(): void {
		$this->startCalled = true;
	}

	public function onRecvEnd(): void {
		$this->endCalled = true;
	}

	public function onRecvCancel(): void {
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
