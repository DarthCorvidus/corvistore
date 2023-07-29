<?php
namespace Net;
interface HubClientListener {
	function getBinary(string $name, int $id): bool;
	function getPacketLength(string $name, int $id): int;
	function onRead(string $name, int $id, string $data);
	function hasWrite(string $name, int $id): bool;
	function onWrite(string $name, int $id): string;
}
