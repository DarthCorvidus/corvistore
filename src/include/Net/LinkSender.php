<?php
namespace Net;
/**
 * Is to be replaced by FileGroup.
 * @deprecated since version 0.1.0
 */
class LinkSender implements StreamSender {
	private \File $file;
	private int $size;
	private int $pos;
	private string $target;
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

	public function onSendCancel(): void {
		
	}

	public function onSendEnd(): void {
		
	}

	public function onSendStart(): void {
		
	}

}
