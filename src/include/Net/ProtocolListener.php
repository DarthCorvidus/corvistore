<?php
namespace Net;
interface ProtocolListener {
	function onQuit();
	function onCommand(string $data, Protocol $protocol);
	function onSerializedPHP(string $data, Protocol $protocol);
}