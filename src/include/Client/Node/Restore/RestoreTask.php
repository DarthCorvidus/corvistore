<?php
namespace Node;
use plibv4\process\Scheduler;
use plibv4\process\Task;
use Net\ProtocolAsync;
class RestoreTask implements Task {
	private RestoreProtocolListener $listener;
	private ProtocolAsync $protocol;
	private array $breadcrumbs = array();
	private string $restoreSource;
	private RestoreQueue $restoreQueue;
	function __construct(\ArgvRestore $argv, ProtocolAsync $protocol, RestoreProtocolListener $listener) {
		$this->restoreSource = $argv->getRestorePath();
		if($this->restoreSource !== "/") {
			$this->breadcrumbs = \Shared::getBreadcrumbs($this->restoreSource);
		}
		$this->protocol = $protocol;
		$this->restoreQueue = $listener->getRestoreQueue();
		$this->listener = $listener;
	}
	public function __tsError(Scheduler $sched, \Exception $e, int $step): void {
		
	}

	public function __tsFinish(Scheduler $sched): void {
		
	}

	public function __tsKill(Scheduler $sched): void {
		
	}

	public function __tsLoop(Scheduler $sched): bool {
		#echo "Expected files: ".$this->listener->expectedFiles.PHP_EOL;
		#echo "Expected Directories: ".$this->listener->expectedDirs.PHP_EOL;
		#echo "File Queue count: ".count($this->listener->fileQueue).PHP_EOL;
		#echo "Dir Queue count: ".count($this->listener->dirQueue).PHP_EOL;
		#echo PHP_EOL;
		if(!empty($this->listener->breadcrumbs)) {
			#$bc = array_pop($this->breadcrumbs);
			#$this->protocol->sendCommand("GET PATH ".$bc);
			#if(empty($this->breadcrumbs)) {
			#	$this->listener->expectedDirs++;
			#	$this->protocol->sendCommand("GET CATALOG ".$this->restoreSource);
			#}
		return true;
		}
		if($this->restoreQueue->expectedFiles>10) {
			//echo "Doing nothing with ".$this->listener->expectedFiles." expected files.".PHP_EOL;
		return true;
		}
		
		if(!empty($this->restoreQueue->versions)) {
			$next = array_shift($this->restoreQueue->versions);
			$this->restoreQueue->expectedFiles++;
			$this->protocol->sendCommand("GET VERSION ".$next);
		return true;
		}
		
		if(!empty($this->restoreQueue->directories)) {
			$next = array_shift($this->restoreQueue->directories);
			$this->protocol->sendCommand("GET CATALOG ".$next);
			#echo "File count: ".count($this->listener->fileQueue).PHP_EOL;
		return true;
		}
		
		
		/**
		 * Psalm lists this as RedundantCondition, possibly because of using
		 * public properties of RestoreProtocolListener.
		 * This will be refactored soon, I just want to commit a working version
		 * before making improvements.
		 * @xpsalm-suppress RedundantCondition
		 */
		#if(empty($this->restoreQueue->directories) && empty($this->restoreQueue->versions) && $this->restoreQueue->expectedDirs === 0 && $this->restoreQueue->expectedFiles === 0) {
		if($this->restoreQueue->isEmpty()) {
			echo "No more directories and files left.".PHP_EOL;
			echo "Done".PHP_EOL;
			$this->protocol->sendCommand("DONE");
		return false;	
		}
	return true;
	}

	public function __tsPause(Scheduler $sched): void {
		
	}

	public function __tsResume(Scheduler $sched): void {
		
	}

	public function __tsStart(Scheduler $sched): void {
		$this->listener->start($this->protocol);
	}

	public function __tsTerminate(Scheduler $sched): bool {
		return true;
	}
}