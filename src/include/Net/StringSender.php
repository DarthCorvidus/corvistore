<?php
namespace Net;
class StringSender implements StreamSender {
	private $content;
	private $pos;
	private $size;
	function __construct(string $string) {
		$this->content = $string;
		$this->pos = 0;
		$this->size = strlen($string);
	}

	public function getSendData(int $amount): string {
		$result = substr($this->content, $this->pos, $amount);
		$this->pos = $this->pos + $amount;
	return $result;
	}

	public function getSendLeft(): int {
		return $this->size-$this->pos;
	}

	public function getSendSize(): int {
		return $this->size;
	}

	public function onSendCancel() {
		
	}

	public function onSendEnd() {
		
	}

	public function onSendStart() {
		
	}

}
