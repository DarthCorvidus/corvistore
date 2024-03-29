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
			$protocol->sendMessage("Status:");
			$protocol->sendMessage("\tConnection #".$this->id);
			$protocol->sendMessage("\tWorker PID #". posix_getpid());
		return;
		}

		if($command == "halt") {
			$protocol->sendMessage("Not yet implemented");
			#echo "Halting SSL server on client ".$this->id." request.".PHP_EOL;
			#exit();
		return;
		}
		
		if($command == "count") {
			
		}
	
		if($command == "quit") {
			echo "Terminating worker for ".$this->id." with PID ".posix_getpid().PHP_EOL;
			exit();
		}
		
		if($command == "srv") {
			$server = $_SERVER;
			$server["date"] = date("Y-m-d H:i:s");
			$protocol->sendSerialize($server);
		return;
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
		echo "Client ".$this->id." disconnected, exiting worker with ".posix_getpid().PHP_EOL;
		exit();
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $command) {
		
	}

	public function onSerialized(\Net\ProtocolReactive $protocol, $unserialized) {
		
	}

	public function onOk(\Net\ProtocolReactive $protocol) {
		
	}

}
