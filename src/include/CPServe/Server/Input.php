<?php
namespace Server;
use plibv4\process\Task;
use plibv4\process\Scheduler;
use Server\AdminProtocolListener;
class Input implements \plibv4\process\Task {
	private Scheduler $sched;
	private \EPDO $pdo;
	function __construct(\EPDO $pdo, Scheduler $sched) {
		$this->sched = $sched;
		$this->pdo = $pdo;
		stream_set_blocking(STDIN, false);
		$this->addBuffer("Corviprotect 0.0.1 Alpha ready.");
	}
	
	public function setProtocolListener(\Net\ProtocolAsyncListener $listener) {
		$this->listener = $listener;
	}
	
	public function __tsError(\Exception $e, int $step): void {
		/**
		 * Let the server die if an exception can't be handled.
		 */
		echo $e->getMessage();
	}

	public function __tsFinish(): void {
		
	}

	public function __tsKill(): void {
		
	}

	public function __tsLoop(): bool {
		if(!empty($this->buffer)) {
			echo array_shift($this->buffer).PHP_EOL;
			if(empty($this->buffer)) {
				echo "> ";
			}
		}
		$read = fgets(STDIN);
		if($read === false) {
			return true;
		}
		$read = trim($read);
		if($read === "") {
			echo ">";
			return true;
		}
		
		if($read === "quit") {
			$this->addBuffer("Not available on console.");
		return true;
		}
		if($read === "halt") {
			$this->sched->__tsTerminate();
		}

		$parser = new \CommandHandler($this->pdo, new \CommandParser($read));
		try {
			$msg = $parser->execute();
			$this->addBuffer($msg);
		} catch (\RuntimeException $e) {
			$this->addBuffer($e->getMessage());
		}
	return true;
	}
	
	function addBuffer($string): void {
		$this->buffer[] = $string;
	}

	public function __tsPause(): void {
		
	}

	public function __tsResume(): void {
		
	}

	public function __tsStart(): void {
		
	}

	public function __tsTerminate(): bool {
		echo "Shutting down server from server shell.".PHP_EOL;
		return true;
	}
}