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
			$protocol->sendMessage("Status: connection #".$this->id);
		return;
		}

		if($command == "halt") {
			echo "Halting SSL server on client ".$this->id." request.".PHP_EOL;
			exit();
		}
		
		if($command == "count") {
			
		}
	
		if($command == "help") {
			$protocol->sendMessage("status - status information");
			$protocol->sendMessage("quit - disconnect client");
			$protocol->sendMessage("halt - shutdown the server");
		return;
		}
		
		$protocol->sendMessage("Invalid command");
	}

	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		echo "Client ".$this->id." disconnected".PHP_EOL;
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $command) {
		
	}

}
