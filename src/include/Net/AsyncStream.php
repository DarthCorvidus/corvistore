<?php
namespace Net;
use \plibv4\process\Task;
use \Net\ProtocolAsync;
use plibv4\process\Scheduler;
class AsyncStream implements Task {
	private mixed $socket;
	private ProtocolAsync $protocol;
	private bool $terminated = false;
	private string $localBuffer = "";
	function __construct(mixed $socket) {
		$this->socket = $socket;
		stream_set_blocking($this->socket, false);
	}
	
	public function setProtocol(ProtocolAsync $protocol): void {
		$this->protocol = $protocol;
	}
	

	public function __tsError(Scheduler $sched, \Exception $e, int $step): void {
		echo "Exception: ".$e->getMessage().PHP_EOL;
		echo "Closing socket due to error".PHP_EOL;
		fclose($this->socket);
	}

	public function __tsFinish(Scheduler $sched): void {
		fclose($this->socket);
		echo "Socket closed.".PHP_EOL;
	}

	public function __tsKill(Scheduler $sched): void {
		fclose($this->socket);
	}

	public function __tsLoop(Scheduler $sched): bool {
		/**
		 * If 'local' buffer is not empty - previous write failed - retry until
		 * local buffer was written, before asking protocol for data again.
		 */
		if($this->localBuffer !== "") {
			$written = fwrite($this->socket, $this->localBuffer);
			$error = error_get_last();
			if(!empty($error)) {
				throw new \Exception($error["message"]);
			}
			if($written===0) {
				return true;
			} else {
				$this->localBuffer = "";
			}
		return true;
		}
		if($this->protocol->hasWrite()) {
			$write = $this->protocol->onWrite();
			$written = fwrite($this->socket, $write);
			/**
			 * If fwrite is unable to write, but data on 'local' buffer.
			 */
			if($written===0) {
				$this->localBuffer = $write;
			}
			$this->protocol->onWritten();
		return true;
		}
		/*
		 * Do not read data if terminated. Just send out what's left.
		 */
		if($this->terminated) {
			return true;
		}
		/**
		 * stream_select cannot be used here, as it will block the script.
		 * Therefore, instead we'll read and check if we got something.
		 */
		#if(@stream_select($readArray, $write, $except, 5, 0)<1) {
		#	return true;
		#}
		$data = fread($this->socket, $this->protocol->getPacketLength());
		/**
		 * I need to look into feof again. feof is not necessarily an error if
		 * the other side was expected to close the connection.
		 */
		if(feof($this->socket)) {
			throw new \Exception("Connection closed.");
		}
		if($data === "") {
			return true;
		}
		if($data === false) {
			return true;
		}
		$this->protocol->onRead($data);
	return true;
	}

	public function __tsPause(Scheduler $sched): void {
		
	}

	public function __tsResume(Scheduler $sched): void {
		
	}

	public function __tsStart(Scheduler $sched): void {
		
	}

	public function __tsTerminate(Scheduler $sched): bool {
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
