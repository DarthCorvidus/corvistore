<?php
namespace Server;
use \plibv4\process\Scheduler;
use \plibv4\process\Task;
use \Net\ProtocolAsyncListener;
class FacadeProtocolListener implements ProtocolAsyncListener {
	private ProtocolAsyncListener $current;
	private Scheduler $sched;
	private Task $task;
	private int $id;
	private \EPDO $pdo;
	function __construct(Scheduler $sched, Task $task) {
		$this->sched = $sched;
		$this->task = $task;
		$this->id = 0;
		$this->pdo = \Shared::getEPDO();
		$this->current = new PreauthProtocolListener($sched, $task, $this);
	}
	
	function switchAdmin(\User $user): void {
		$this->current = new AdminProtocolListener($this->sched, $this->task, $this->pdo, $this->id, $user);
	}
	
	function switchNode(\Node $node): void {
		$this->current = new NodeProtocolListener($this->sched, $this->task, $this->pdo, $this->id, $node);
	}

	public function onCommand(\Net\ProtocolAsync $protocol, string $command): void {
		$this->current->onCommand($protocol, $command);
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol): void {
		$this->current->onDisconnect($protocol);
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message): void {
		$this->current->onMessage($protocol, $message);
	}

	public function onOk(\Net\ProtocolAsync $protocol): void {
		$this->current->onOk($protocol);
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, mixed $unserialized): void {
		$this->current->onSerialized($protocol, $unserialized);
	}

	public function onBinaryClass(\Net\ProtocolAsync $protocol, string $classname, string $classdata): void {
		$this->current->onBinaryClass($protocol, $classname, $classdata);
	}

	public function onStreamEnd(\Net\ProtocolAsync $protocol, \Net\StreamReceiver $streamReceiver): void {
		$this->current->onStreamEnd($protocol, $streamReceiver);
	}

	public function onStreamStart(\Net\ProtocolAsync $protocol, \Net\StreamReceiver $streamReceiver): void {
		$this->current->onStreamStart($protocol, $streamReceiver);
	}
}
