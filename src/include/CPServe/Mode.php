<?php
interface Mode {
	function onCommand(string $string);
	function onStructuredData(string $data);
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
	function onServerMessage(string $message);
	function isQuit(): bool;
}
