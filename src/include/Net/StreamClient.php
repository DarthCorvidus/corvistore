<?php
namespace Net;
class StreamClient implements Stream {
	private mixed $socket;
	function __construct(mixed $socket) {
		$this->socket = $socket;
		// Make sure stream is blocking.
		stream_set_blocking($this->socket, true);
		/**
		 * There's currently a bug which sometimes blocks reading for the
		 * amount of a timeout. It happens if serialized GET CATALOG results
		 * are ($length + 5) % 1024 = 0, ie if the serialized string + 5 bytes
		 * for payload are an exact multiple of 1024. I was not able to determine
		 * the cause.
		 * StreamClient is able to recover after timeout is reached. I don't
		 * understand why, but currently I'm considering to go for another approach
		 * regarding client/server communication.
		 */
		stream_set_timeout($this->socket, 5);
	}
	public function close(): void {
		fclose($this->socket);
	}

	public function read(int $amount): string {
		while(true) {
			$write = array();
			$read = array($this->socket);
			/*
			 * stream_select should/must not be used with blocking streams.
			 */
			#if(@stream_select($read, $write, $except, $tv_sec = 1) < 1) {
			#	echo "Stream not ready to read.".PHP_EOL;
			#	continue;
			#}
			$data = fread($this->socket, $amount);
			if($data === false) {
				echo "Read false instead of data, continuing.".PHP_EOL;
				continue;
			}
			$received = strlen($data);
			if($received === 0) {
				continue;
			}
			if($received<$amount) {
				throw new \Exception("Received less data than expected, ".$received." instead of ".$amount);
			}
			return $data;
			// fread($this->socket, $amount);
		}
	}

	public function write(string $data): int {
		while(true) {
			$write = array($this->socket);
			$read = array();
			#if(@stream_select($read, $write, $except, $tv_sec = 1) < 1) {
			#	echo "Stream not ready to write.".PHP_EOL;
			#	continue;
			#}
			return fwrite($this->socket, $data);
		}
	}

}
