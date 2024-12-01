<?php
namespace Server;
use \plibv4\process\Scheduler;
use \plibv4\process\Task;
class PreauthProtocolListener implements \Net\ProtocolAsyncListener {
	private string $mode = "";
	private Scheduler $sched;
	private Task $task;
	private int $id;
	private ?\User $user = null;
	private \EPDO $pdo;
	private $username;
	private $password;
	private FacadeProtocolListener $listener;
	function __construct(Scheduler $sched, Task $task, FacadeProtocolListener $listener) {
		$this->sched = $sched;
		$this->task = $task;
		$this->id = 0;
		$this->pdo = \Shared::getEPDO();
		$this->listener = $listener;
	}
	
	public function onCommand(\Net\ProtocolAsync $protocol, string $command): void {
		echo $command.PHP_EOL;
		if($this->mode === "") {
			$this->modeSelect($protocol, $command);
		return;
		}
		if($this->user == null) {
			$this->authenticate($protocol, $command);
		return;
		}
		//echo $command.PHP_EOL;
		if($command=="quit") {
			echo "Terminating session for ".$this->id.PHP_EOL;
			//$protocol->sendMessage("Ended session on ".date("Y-m-d H:i:s"));
			//$protocol->sendMessage("Goodbye.");
			$this->sched->terminate($this->task);
		}
		if($command=="exit") {
			$protocol->sendMessage("Ended session at ".date("Y-m-d H:i:s"));
			$protocol->sendMessage("Goodbye.");
			$this->sched->terminate($this->task);
		return;
		}
		
		if($command == "halt") {
			$protocol->sendMessage("Shutting down server at ".date("Y-m-d H:i:s"));
			/*
			 * End the scheduler. Not /quite/ correct here, as PHP does not know
			 * that sched actually implements Task itself here.
			 */
			$this->sched->__tsTerminate();
		return;
		}
		$protocol->sendMessage("You sent: ".$command);
	}
	
	private function modeSelect(\Net\ProtocolAsync $protocol, string $command): void {
		if($command=="quit") {
			$this->sched->terminate($this->task);
		return;
		}
		$exp = explode(" ", $command);
		if(count($exp)!=2) {
			echo "Malformed mode select from ".$this->id.PHP_EOL;
			$this->sched->terminate($this->task);
		return;
		}
		if($exp[0]!="mode") {
			echo "Malformed mode select from ".$this->id.PHP_EOL;
			$this->sched->terminate($this->task);
		return;
		}
		
		if($exp[0]=="mode" && !in_array($exp[1], array("admin", "node", TRUE))) {
			echo "Unknown mode from ".$this->id.PHP_EOL;
			$this->sched->terminate($this->task);
		return;
		}
		
		if($exp[0]=="mode" && in_array($exp[1], array("admin", "node", TRUE))) {
			echo "Client ".$this->id." selected ".$exp[1].PHP_EOL;
			$this->mode = $exp[1];
			$protocol->sendOK();
			$protocol->expect(\Net\ProtocolAsync::COMMAND);
		return;
		}
	}
	
	private function authenticate(\Net\ProtocolAsync $protocol, string $command): void {
		$exp = explode(" ", $command);
		if(count($exp)!=2) {
			echo $command.PHP_EOL;
			echo "Malformed authentication from ".$this->id.PHP_EOL;
			$this->sched->terminate($this->task);
		return;
		}
		$cred = explode(":", $exp[1]);
		if(count($cred)!=2) {
			echo "Malformed credentials from ".$this->id.PHP_EOL;
			$this->sched->terminate($this->task);
		return;
		}
		if($this->mode == "admin") {
			$this->username = $cred[0];
			$this->password = $cred[1];
			$this->authenticateAdmin($protocol);
		}
		if($this->mode == "node") {
			$this->username = $cred[0];
			$this->password = $cred[1];
			$this->authenticateNode($protocol);
		}
	}
	
	private function authenticateAdmin(\Net\ProtocolAsync $protocol): void {
		try {
			$this->user = \User::authenticate($this->pdo, $this->username.":".$this->password);
			echo "Authentication for client ".$this->id.", username ".$this->username." suceeded".PHP_EOL;
		} catch (Exception $ex) {
			echo "Authentication for client ".$this->id.", username ".$this->username." failed".PHP_EOL;
			$this->sched->terminate($this->task);
		}
		$protocol->sendMessage("Welcome to Corviprotect 0.0.1 Alpha");
		$this->listener->switchAdmin($this->user);
		//$protocol->sendMessage("Corviprotect v0.0.1 Alpha");
	}

	private function authenticateNode(\Net\ProtocolAsync $protocol): void {
		try {
			$node = \Node::authenticate($this->pdo, $this->username.":".$this->password);
			echo "Authentication for client ".$this->id.", node ".$this->username." suceeded".PHP_EOL;
		} catch (Exception $ex) {
			echo "Authentication for client ".$this->id.", node ".$this->username." failed".PHP_EOL;
			$this->sched->terminate($this->task);
		}
		$this->listener->switchNode($node);
		//$protocol->sendMessage("Corviprotect v0.0.1 Alpha");
	}
	
	
	

	public function onDisconnect(\Net\ProtocolAsync $protocol): void {
		
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message): void {
		
	}

	public function onOk(\Net\ProtocolAsync $protocol): void {
		
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, mixed $unserialized): void {
		
	}

	public function onBinaryClass(\Net\ProtocolAsync $protocol, object $instance): void {
		
	}
}