<?php
interface StreamHubListener {
	function onRead(string $name, int $id, $stream);
	function onWrite(string $name, int $id, $stream);
	function onConnect(string $name, int $id, $newClient);
}
