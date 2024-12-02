<?php
use plibv4\process\Task;
use plibv4\process\Scheduler;
class Idle implements Task {
	private int $delayMilliseconds = 0;
	private int $delaySeconds = 0;
	
	public function __construct(int $seconds, int $milliseconds) {
		$this->setDelay($seconds, $milliseconds);
	}
	
	public function setDelay(int $seconds, int $milliseconds): void {
		if($seconds<0) {
			throw new \InvalidArgumentException("seconds must not be negative");
		}
		if($milliseconds<0) {
			throw new \InvalidArgumentException("milliseconds must not be negative");
		}
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
		/**
		 * @psalm-var positive-int $this->delaySeconds
		 * @psalm-var positive-int $this->delayMilliseconds
		 */
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