<?php
namespace Server;
class AdminProtocolListener implements \Net\ProtocolReactiveListener {
	private $clientId;
	private $user;
	private $pdo;
	public function __construct(\EPDO $pdo, int $clientId, \User $user) {
		$this->clientId = $clientId;
		$this->user = $user;
		$this->pdo = $pdo;
	}
	public function onCommand(\Net\ProtocolReactive $protocol, string $command) {
		echo "Received ".$command.PHP_EOL;
		if($command == "status") {
			$protocol->sendMessage("Status:");
			$protocol->sendMessage("\tConnection #".$this->clientId);
			$protocol->sendMessage("\tWorker PID #".posix_getpid());
			$protocol->sendMessage("\tUser:       ".$this->user->getName());
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
			echo "Terminating worker for ".$this->clientId." with PID ".posix_getpid().PHP_EOL;
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
		$handler = new \CommandHandler($this->pdo, new \CommandParser($command));
		try {
			$msg = $handler->execute();
			$protocol->sendMessage($msg);
		} catch (\Exception $ex) {
			$protocol->sendMessage($ex->getMessage());
		}
	}

	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		echo "Client ".$this->clientId." disconnected, exiting worker with ".posix_getpid().PHP_EOL;
		exit();
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $command) {
		
	}

	public function onSerialized(\Net\ProtocolReactive $protocol, $unserialized) {
		
	}

	public function onOk(\Net\ProtocolReactive $protocol) {
		
	}

}