<?php
namespace Storage;
use plibv4\process\Task;
use plibv4\process\Scheduler;
/**
 * TaskSingleStorage is a concurrent way to store files in 'one go'; this is
 * used for files smaller than < 1 MB in combination with FileGroup.
 */
class TaskSingleStorage implements Task {
	/** @var list<StorageJob> */
	private array $queue = [];
	private \Storage $storage;
	public function __construct(\Storage $storage) {
		$this->storage = $storage;
	}
	
	public function addStorageJob(StorageJob $storageJob): void {
		$this->queue[] = $storageJob;
	}
	
	public function __tsError(Scheduler $sched, \Exception $e, int $step): void {
		throw $e;
	}

	public function __tsFinish(Scheduler $sched): void {
		
	}

	public function __tsKill(Scheduler $sched): void {
		
	}

	public function __tsLoop(Scheduler $sched): bool {
		if(empty($this->queue)) {
			return true;
		}
		$job = $this->getStorageJob();
		$count = count($this->queue);
		if($count % 50 == 0) {
			echo "Storing ".$job->file->getPath().", ".$count." left".PHP_EOL;
		}
		$this->storage->storeSingle($job);
		
	return true;
	}
	
	public function getStorageJob(): StorageJob {
		return array_shift($this->queue);
	}

	public function __tsPause(Scheduler $sched): void {
		
	}

	public function __tsResume(Scheduler $sched): void {
		
	}

	public function __tsStart(Scheduler $sched): void {
		
	}

	public function __tsTerminate(Scheduler $sched): bool {
		if(!empty($this->queue)) {
			return false;
		}
	return true;
	}
}