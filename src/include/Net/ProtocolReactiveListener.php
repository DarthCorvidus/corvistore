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
interface ProtocolReactiveListener {
	function onMessage(\Net\ProtocolReactive $protocol, string $message);
	function onCommand(\Net\ProtocolReactive $protocol, string $command);
	function onDisconnect(\Net\ProtocolReactive $protocol);
}
