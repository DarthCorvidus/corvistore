<?php
namespace Net;
class StringSender implements StreamSender {
	private $content;
	private $pos;
	private $size;
	private $type;
	function __construct(int $type, string $string) {
		$this->content = $string;
		$this->pos = 0;
		$this->size = strlen($string);
		$this->type = $type;
	}
	
	public function getSendType(): int {
		return $this->type;
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
