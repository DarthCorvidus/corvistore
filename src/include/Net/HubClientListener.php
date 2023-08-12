<?php
namespace Net;
interface HubClientListener {
	function getBinary(): bool;
	function getPacketLength(): int;
	function onRead(string $data);
	function hasWrite(): bool;
	function onWrite(): string;
	function onDisconnect();
}
