<?php
/*
 * Written by Bing/ChatGPT
 */
namespace Net;
class MockSender implements StreamSender {
    private $data;
    private $pointer = 0;
    private $exceptionAfter = PHP_INT_MAX;
    private $started = false;
    private $ended = false;
    private $cancelled = false;

    public function __construct(string $data) {
        $this->data = $data;
    }

    public function getSendType(): int {
        return 1;
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

    public function onSendStart() {
        $this->started = true;
    }

    public function onSendEnd() {
        $this->ended = true;
    }

    public function onSendCancel() {
        $this->cancelled = true;
    }

    public function setExceptionAfter(int $bytes) {
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