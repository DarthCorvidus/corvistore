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
	
	function switchAdmin(\User $user) {
		$this->current = new AdminProtocolListener($this->sched, $this->task, $this->pdo, $this->id, $user);
	}

	public function onCommand(\Net\ProtocolAsync $protocol, string $command) {
		$this->current->onCommand($protocol, $command);
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol) {
		$this->onDisconnect($protocol);
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message) {
		$this->onMessage($protocol, $message);
	}

	public function onOk(\Net\ProtocolAsync $protocol) {
		$this->onOk($protocol);
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, $unserialized) {
		$this->onSerialized($protocol, $unserialized);
	}
}
