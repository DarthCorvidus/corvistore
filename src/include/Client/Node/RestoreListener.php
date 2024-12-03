<?php
namespace Node;
/**
 * @deprecated since version 0.0.1
 */
class RestoreListener implements \Net\TransferListener {
	private $target;
	private $handle;
	function __construct(string $target) {
		$this->target = $target;
	}
	public function onCancel() {
		fclose($this->handle);
		unlink($this->target);
	}

	public function onData(string $data): void {
		fwrite($this->handle, $data);
	}

	public function onEnd(): void {
		fclose($this->handle);
	}

	public function onFail(): void {
		
	}

	public function onStart(int $size) {
		$this->handle = fopen($this->target, "w");
	}

}