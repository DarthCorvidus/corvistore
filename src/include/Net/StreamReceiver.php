<?php
namespace Net;
interface StreamReceiver {
	function setRecvSize(int $size);
	function getRecvSize(): int;
	function receiveData(string $data);
	function getRecvLeft(): int;
	function onRecvStart();
	function onRecvEnd();
	function onRecvCancel();
}