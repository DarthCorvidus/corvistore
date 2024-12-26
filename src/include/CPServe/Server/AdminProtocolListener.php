<?php
namespace Server;
use plibv4\process\Scheduler;
use plibv4\process\Task;
class AdminProtocolListener implements \Net\ProtocolAsyncListener {
	private int $clientId;
	private \User $user;
	private \EPDO $pdo;
	private Scheduler $sched;
	private Task $task;
	public function __construct(Scheduler $sched, Task $task, \EPDO $pdo, int $clientId, \User $user) {
		$this->clientId = $clientId;
		$this->user = $user;
		$this->pdo = $pdo;
		$this->sched = $sched;
		$this->task = $task;
	}
	
	public function onCommand(\Net\ProtocolAsync $protocol, string $command): void {
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
			echo "Halting SSL server on client ".$this->clientId." request.".PHP_EOL;
			$this->sched->terminateAll();
		return;
		}
		
		if($command == "count") {
			
		}
	
		if($command == "quit") {
			echo "Terminating client ".$this->clientId.PHP_EOL;
			$this->sched->terminate($this->task);
			//exit();
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

	public function onDisconnect(\Net\ProtocolAsync $protocol): void {
		echo "Client ".$this->clientId." disconnected, exiting worker with ".posix_getpid().PHP_EOL;
		exit();
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message): void {
		throw new \RuntimeException("not implemented");
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, mixed $unserialized): void {
		throw new \RuntimeException("not implemented");
	}

	public function onOk(\Net\ProtocolAsync $protocol): void {
		throw new \RuntimeException("not implemented");
	}

	public function onBinaryClass(\Net\ProtocolAsync $protocol, string $classname, string $classdata): void {
		throw new \RuntimeException("not implemented");
	}

	public function onStreamEnd(\Net\ProtocolAsync $protocol, \Net\StreamReceiver $streamReceiver): void {
		throw new \RuntimeException("no file stream expected in ".self::class);
	}

	public function onStreamStart(\Net\ProtocolAsync $protocol, \Net\StreamReceiver $streamReceiver): void {
		throw new \RuntimeException("no file stream expected in ".self::class);
	}
}