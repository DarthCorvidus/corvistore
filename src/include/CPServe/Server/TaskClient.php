<?php
namespace Server;
class TaskClient implements \plibv4\process\Task {
	private \plibv4\process\Timeshare $ts;
	private $socket;
	function __construct(\plibv4\process\Timeshare $ts, $socket) {
		$this->ts = $ts;
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
		$value = fread($this->socket, 1024);
		//$value = trim(fgets($this->socket));
		if($value === "" or $value === false) {
			return true;
		}
		var_dump($value);
	return true;
	}

	public function __tsPause(): void {
		
	}

	public function __tsResume(): void {
		
	}

	public function __tsStart(): void {
		
	}

	public function __tsTerminate(): bool {
		fclose($this->socket);
	return true;
	}
}
