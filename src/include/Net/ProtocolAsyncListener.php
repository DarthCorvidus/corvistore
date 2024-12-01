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
	function onMessage(\Net\ProtocolAsync $protocol, string $message): void;
	function onCommand(\Net\ProtocolAsync $protocol, string $command): void;
	function onDisconnect(\Net\ProtocolAsync $protocol): void;
	function onSerialized(\Net\ProtocolAsync $protocol, mixed $unserialized): void;
	function onOk(\Net\ProtocolAsync $protocol): void;
	function onBinaryClass(ProtocolAsync $protocol, object $instance): void;
}
