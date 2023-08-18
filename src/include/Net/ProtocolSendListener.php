<?php
namespace Net;
interface ProtocolSendListener {
	function onSent(ProtocolReactive $protocol);
}
