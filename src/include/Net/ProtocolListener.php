<?php
namespace Net;
interface ProtocolListener {
	function onQuit();
	function onCommand(string $data, Protocol $protocol);
}