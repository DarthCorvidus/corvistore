<?php
namespace Node;
use \Net\ProtocolAsyncListener;
use Net\ProtocolAsync;
use plibv4\process\Scheduler;
class ProtocolNode implements ProtocolAsyncListener, DirectoryWalkObserver {
	private ProtocolAsync $protocol;
	private Scheduler $scheduler;
	private $done = false;
	private $paused = false;
	function __construct() {
		;
	}
	
	public function setProtocol(\Net\ProtocolAsync $protocol) {
		$this->protocol = $protocol;
	}
	
	public function setScheduler(Scheduler $sched) {
		$this->scheduler = $sched;
	}
	
	public function onCommand(\Net\ProtocolAsync $protocol, string $command) {
		echo $command;
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol) {
		//$this->scheduler->terminate();
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message) {
		echo $message.PHP_EOL;
	}

	public function onOk(\Net\ProtocolAsync $protocol) {
		if($this->done == true) {
			$this->protocol->sendCommand("QUIT");
		}
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, $unserialized) {
		$this->queue--;
		if($this->queue==0 && $this->paused == true) {
			echo "Resuming Iterator with queue entries ".$this->queue.PHP_EOL;
			$this->scheduler->resume($this->task);
			$this->paused = false;
		}
	}

	public function onEnd(\plibv4\process\Task $task): void {
		$this->protocol->sendCommand("DONE");
		$this->done = true;
	}

	public function onFiles(\plibv4\process\Task $task, string $dir, \Files $files): void {
		$this->protocol->sendCommand("GET CATALOG ".$dir);
		$this->queue++;
		if($this->queue>=100 && $this->paused == false) {
			echo "Pausing Iterator with queue entries ".$this->queue.PHP_EOL;
			$this->scheduler->pause($task);
			$this->paused = true;
		}
		$this->task = $task;
	}
}