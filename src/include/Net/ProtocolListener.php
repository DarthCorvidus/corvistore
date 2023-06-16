<?php
namespace Net;
interface ProtocolListener {
	function onQuit();
	function onCommand(string $data);
	function onSerializedPHP(string $data);
}