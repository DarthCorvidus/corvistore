<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Net;

/**
 *
 * @author hm
 */
interface ProtocolAsyncListener {
	function onMessage(\Net\ProtocolAsync $protocol, string $message);
	function onCommand(\Net\ProtocolAsync $protocol, string $command);
	function onDisconnect(\Net\ProtocolAsync $protocol);
	function onSerialized(\Net\ProtocolAsync $protocol, $unserialized);
	function onOk(\Net\ProtocolAsync $protocol);
}
