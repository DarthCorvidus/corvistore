<?php
namespace Net;
interface StreamSender {
	function getSize(): int;
	function getData(int $amount): string;
	function getLeft(): int;
	function onStart();
	function onEnd();
	function onCancel();
}