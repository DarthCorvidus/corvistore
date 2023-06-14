<?php
interface Mode {
	function onServerMessage(string $message);
	function isQuit(): bool;
}
