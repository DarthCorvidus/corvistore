<?php
use plibv4\process\Task;
use plibv4\process\Scheduler;
class Idle implements Task {
	private int $delayMilliseconds = 0;
	private int $delaySeconds = 0;
	
	public function __construct(int $seconds, int $milliseconds) {
		$this->delaySeconds = $seconds;
		$this->delayMilliseconds = $milliseconds*1000000;
	}
	
	public function setDelay(int $seconds, int $milliseconds) {
		$this->delaySeconds = $seconds;
		$this->delayMilliseconds = $milliseconds*1000000;
	}


	public function __tsError(Scheduler $sched, \Exception $e, int $step): void {
		
	}

	public function __tsFinish(Scheduler $sched): void {
		
	}

	public function __tsKill(Scheduler $sched): void {
		
	}

	public function __tsLoop(Scheduler $sched): bool {
		time_nanosleep($this->delaySeconds, $this->delayMilliseconds);
		return true;
	}

	public function __tsPause(Scheduler $sched): void {
	}

	public function __tsResume(Scheduler $sched): void {
	}

	public function __tsStart(Scheduler $sched): void {
		
	}

	public function __tsTerminate(Scheduler $sched): bool {
		return true;
	}
}