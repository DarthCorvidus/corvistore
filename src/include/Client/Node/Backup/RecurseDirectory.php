<?php
namespace Node;
use plibv4\process\Task;
use plibv4\process\Scheduler;
class RecurseDirectory implements Task {
	private string $path;
	private int $processed = 0;
	private array $dirStack = array();
	private \InEx $inex;
	private DirectoryWalkObserver $walkObserver;
	function __construct(string $path, \InEx $inex, DirectoryWalkObserver $walkObserver) {
		$this->path = realpath($path);
		$this->dirStack[] = $this->path;
		$this->inex = $inex;
		$this->walkObserver = $walkObserver;
	}
	
	public function __tsError(Scheduler $sched, \Exception $e, int $step): void {
		echo $e::class.PHP_EOL;
		print $e->getTraceAsString().PHP_EOL;
		throw $e;
	}

	public function __tsFinish(Scheduler $sched): void {
		echo "Processed: ".$this->processed.PHP_EOL;
	}

	public function __tsKill(Scheduler $sched): void {
		
	}

	public function __tsLoop(Scheduler $sched): bool {
		if(empty($this->dirStack)) {
			$this->walkObserver->onEnd($this);
			return false;
		}
		$next = array_shift($this->dirStack);
		//echo count($this->dirStack).PHP_EOL;
		$files = new \Files();
		$files = \Files::fromDirectory($next, $this->inex);
		$directories = $files->getDirectories();
		for($i=0;$i<$directories->getCount();$i++) {
			$this->dirStack[] = $directories->getEntry($i)->getPath();
		}
		$this->walkObserver->onFiles($this, $next, $files);
	return true;
	}
	
	public function getSplFileInfo(\SplFileInfo $info): \SplFileInfo {
		return $info;
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