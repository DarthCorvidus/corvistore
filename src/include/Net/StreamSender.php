<?php
namespace Net;
interface StreamSender {
	function getSendSize(): int;
	function getSendData(int $amount): string;
	function getSendLeft(): int;
	function onSendStart();
	function onSendEnd();
	function onSendCancel();
}