<?php
/*
 * Written by Bing/ChatGPT
 */
namespace Net;
class MockSender implements StreamSender {
    private string $data;
    private int $pointer = 0;
    private int $exceptionAfter = PHP_INT_MAX;
    private bool $started = false;
    private bool $ended = false;
    private bool $cancelled = false;
    
	public function __construct(string $data) {
        $this->data = $data;
    }

    public function getSendType(): int {
        return \Net\Protocol::FILE;
    }

    public function getSendSize(): int {
        return strlen($this->data);
    }

    public function getSendData(int $amount): string {
        if ($this->pointer + $amount > $this->exceptionAfter) {
            throw new \RuntimeException("Exception after {$this->exceptionAfter} Bytes");
        }
        $result = substr($this->data, $this->pointer, $amount);
        $this->pointer += strlen($result);
        return $result;
    }

    public function getSendLeft(): int {
        return strlen($this->data) - $this->pointer;
    }

    public function onSendStart(): void {
		// onSendStart should fail if $this->exceptionAfter is zero.
		if($this->exceptionAfter==0) {
			$this->cancelled = TRUE;
			$this->started = true;
			throw new \RuntimeException("Unable to start sender.");
		}
        $this->started = true;
    }

    public function onSendEnd(): void {
        $this->ended = true;
    }

    public function onSendCancel(): void {
        $this->cancelled = true;
    }

    public function setExceptionAfter(int $bytes): void {
        $this->exceptionAfter = $bytes;
    }

    public function hasStarted(): bool {
        return $this->started;
    }

    public function hasEnded(): bool {
        return $this->ended;
    }

    public function wasCancelled(): bool {
        return $this->cancelled;
    }
}