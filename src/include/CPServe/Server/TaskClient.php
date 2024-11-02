<?php
namespace Server;
class TaskClient implements \plibv4\process\Task {
	private \plibv4\process\Timeshare $ts;
	private $socket;
	private \Net\ProtocolAsync $protocol;
	private bool $terminated = false;
	function __construct(\plibv4\process\Timeshare $ts, $socket) {
		$this->ts = $ts;
		$this->protocol = new \Net\ProtocolAsync(new FacadeProtocolListener($this->ts, $this));
		$this->socket = $socket;
		stream_set_blocking($this->socket, false);
	}

	public function __tsError(\Exception $e, int $step): void {
		
	}

	public function __tsFinish(): void {
		fclose($this->socket);
		echo "Socket closed.".PHP_EOL;
	}

	public function __tsKill(): void {
		fclose($this->socket);
	}

	public function __tsLoop(): bool {
		if($this->protocol->hasWrite()) {
			$write = $this->protocol->onWrite();
			$written = fwrite($this->socket, $write);
			$this->protocol->onWritten();
		return true;
		}
		/*
		 * Do not read data if terminated. Just send out what's left.
		 */
		if($this->terminated) {
			return true;
		}
		$data = fread($this->socket, $this->protocol->getPacketLength());
		if($data === "" or $data === false) {
			return true;
		}
		$this->protocol->onRead($data);
	return true;
	}

	public function __tsPause(): void {
		
	}

	public function __tsResume(): void {
		
	}

	public function __tsStart(): void {
		
	}

	public function __tsTerminate(): bool {
		/*
		 * Do not terminate as long Protocol has data left in buffer.
		 */
		if($this->protocol->hasWrite()) {
			return false;
		}
		fclose($this->socket);
	return true;
	}
}
