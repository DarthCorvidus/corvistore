<?php
namespace Net;
interface StreamSender {
	function getSendType(): int;
	function getSendSize(): int;
	function getSendData(int $amount): string;
	function getSendLeft(): int;
	function onSendStart(): void;
	function onSendEnd(): void;
	function onSendCancel(): void;
}