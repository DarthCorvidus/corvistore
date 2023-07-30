<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SSLProtocolListener
 *
 * @author hm
 */
class SSLProtocolListener implements \Net\ProtocolReactiveListener {
	private $id;
	public function __construct(int $id) {
		$this->id = $id;
	}
	public function onCommand(\Net\ProtocolReactive $protocol, string $command) {
		echo "Received ".$command.PHP_EOL;
		if($command == "status") {
			$protocol->sendString(\Net\ProtocolReactive::MESSAGE, "Status: connection #".$this->id);
		return;
		}

		if($command == "halt") {
			echo "Halting SSL server on client ".$this->id." request.".PHP_EOL;
			exit();
		}
		
		if($command == "help") {
			$protocol->sendString(\Net\ProtocolReactive::MESSAGE, "status - status information");
			$protocol->sendString(\Net\ProtocolReactive::MESSAGE, "quit - disconnect client");
			$protocol->sendString(\Net\ProtocolReactive::MESSAGE, "halt - shutdown the server");
		return;
		}
		
		$protocol->sendString(\Net\ProtocolReactive::MESSAGE, "Invalid command");
	}

	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		echo "Client ".$this->id." disconnected".PHP_EOL;
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $command) {
		
	}

}
