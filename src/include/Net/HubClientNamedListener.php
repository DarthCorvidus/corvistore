<?php
namespace Net;
interface HubClientNamedListener {
	function getBinary(string $name, int $id): bool;
	function getPacketLength(string $name, int $id): int;
	function onRead(string $name, int $id, string $data);
	function hasWrite(string $name, int $id): bool;
	function onWrite(string $name, int $id): string;
	function onWritten(string $name, int $id);
	function onDisconnect(string $name, int $id);
}
