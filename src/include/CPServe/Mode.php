<?php
interface Mode {
	function onCommand(string $string): void;
	function onStructuredData(string $data): void;
	/*
	// First batch of raw data is sent
	function onRawStart(string $data);
	// Raw data is sent
	function onRawMiddle(string $data);
	// Last batch of raw data is sent
	function onRawEnd(string $data);
	// Other party cancels sending of raw data
	function onRawCancel(string $data);
	// Small amount of raw data is sent
	function onRawSmall(string $data);
	
	function onCancel();
	function onOK();
	 * 
	 */
	function onServerMessage(string $message): void;
	function isQuit(): bool;
}
