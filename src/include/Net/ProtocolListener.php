<?php
namespace Net;
/**
 * @deprecated since version 0.0.1
 */
interface ProtocolListener {
	function onQuit(): void;
	function onCommand(string $data, Protocol $protocol): void;
}