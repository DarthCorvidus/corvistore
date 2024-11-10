<?php
namespace Node;
use plibv4\process\Task;
class IterateDirectory implements Task {
	private $path;
	private $currentPath;
	private array $directories = array();
	private \FilesystemIterator $fsit;
	private \RecursiveDirectoryIterator $rdi;
	private \RecursiveIteratorIterator $rii;
	private DirFilter $filtered;
	private int $processed = 0;
	private \Net\ProtocolSync $protocol;
	private array $dirStack = array();
	function __construct(string $path, \InEx $inex) {
		$this->path = $path;
		$this->currentPath = $path;
		$this->rdi = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS | ~\RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
		/*
		 * Hardcoded for now as long as evaluations are done.
		 */
		$inex = new \InEx();
		$inex->addInclude("/etc");
		$this->filtered = new DirFilter($this->rdi, $inex);
		$this->rii = new \RecursiveIteratorIterator($this->filtered, \RecursiveIteratorIterator::SELF_FIRST);
		$this->rii->rewind();
	}
	
	public function __tsError(\Exception $e, int $step): void {
		
	}

	public function __tsFinish(): void {
		echo "Processed: ".$this->processed.PHP_EOL;
	}

	public function __tsKill(): void {
		
	}

	public function __tsLoop(): bool {
		if(!$this->rii->valid()) {
			return false;
		}
		$this->processed++;
		$fileInfo = $this->getSplFileInfo($this->rii->current());
		if($fileInfo->isLink()) {
			//echo str_replace("//", "/", $fileInfo->getPathname()).PHP_EOL;
			$this->rii->next();
			return true;
		}
		
		if($fileInfo->isFile()) {
			$this->dirStack[] = $fileInfo->getFileInfo();
		}
		
		if($fileInfo->isDir() && !empty($this->dirStack)) {
			$this->dirStack = array();
		}
		$this->rii->next();
	return true;
	}
	
	public function getSplFileInfo(\SplFileInfo $info): \SplFileInfo {
		return $info;
	}

	public function __tsPause(): void {
		
	}

	public function __tsResume(): void {
		
	}

	public function __tsStart(): void {
		
	}

	public function __tsTerminate(): bool {
		
	}
}