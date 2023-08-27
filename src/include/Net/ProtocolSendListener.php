<?php
namespace Net;
interface ProtocolSendListener {
	function onSent(ProtocolAsync $protocol);
}
