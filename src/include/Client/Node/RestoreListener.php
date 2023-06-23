<?php
namespace Node;
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

	public function onData(string $data) {
		fwrite($this->handle, $data);
	}

	public function onEnd() {
		fclose($this->handle);
	}

	public function onFail() {
		
	}

	public function onStart(int $size) {
		$this->handle = fopen($this->target, "w");
	}

}