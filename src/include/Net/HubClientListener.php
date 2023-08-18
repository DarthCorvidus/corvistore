<?php
namespace Net;
interface HubClientListener {
	/**
	 * Check if a stream should be treated as binary (fread) or human readable
	 * (fgets, like STDIN).
	 */
	function getBinary(): bool;
	/**
	 * get packet length (only for binary), ie how many bytes should be read
	 * every fread.
	 */
	function getPacketLength(): int;
	/**
	 * Will be called if a stream has data to read.
	 * @param string $data
	 */
	function onRead(string $data);
	/**
	 * Check if HubClientListener has data to write.
	 */
	function hasWrite(): bool;
	/**
	 * Get data if hasWrite is true
	 */
	function onWrite(): string;
	/**
	 * Will be called after the data has been written to a stream.
	 */
	function onWritten();
	function onDisconnect();
}
