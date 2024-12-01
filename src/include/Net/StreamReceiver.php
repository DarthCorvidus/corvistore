<?php
namespace Net;
interface StreamReceiver {
	function setRecvSize(int $size): void;
	function getRecvSize(): int;
	function receiveData(string $data): void;
	function getRecvLeft(): int;
	function onRecvStart(): void;
	function onRecvEnd(): void;
	function onRecvCancel(): void;
}