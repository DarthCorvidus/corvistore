<?php
namespace Net;
class LinkSender implements StreamSender {
	private $file;
	private $size;
	private $pos;
	private $target;
	function __construct(\File $file) {
		$this->file = $file;
		$this->target = $this->file->getTarget();
		$this->pos = 0;
		$this->size = strlen($this->target);
	}
	
	public function getSendData(int $amount): string {
		$return = substr($this->target, $this->pos, $amount);
		$this->pos += $amount;
	return $return;
	}

	public function getSendLeft(): int {
		return $this->size - $this->pos;
	}

	public function getSendSize(): int {
		return $this->size;
	}

	public function getSendType(): int {
		return \Net\Protocol::FILE;
	}

	public function onSendCancel() {
		
	}

	public function onSendEnd() {
		
	}

	public function onSendStart() {
		
	}

}
