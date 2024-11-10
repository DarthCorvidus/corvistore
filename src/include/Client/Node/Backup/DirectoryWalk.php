<?php
namespace Node;
use plibv4\process\Task;
use plibv4\process\Scheduler;
class DirectoryWalk implements Task {
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
	private \InEx $inex;
	private $repeat = 0;
	private \DirectoryIterator $currentDir;
	private \Files $currentFiles;
	private DirectoryWalkObserver $observer;
	function __construct(string $path, \InEx $inex, DirectoryWalkObserver $observer) {
		$this->path = $path;
		$this->dirStack[] = $this->path;
		$this->inex = $inex;
		$this->inex = new \InEx();
		$this->inex->addInclude("/etc");
		$this->currentFiles = new \Files();
		$this->currentDir = new \DirectoryIterator($this->path);
		$this->observer = $observer;
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
		if(!$this->currentDir->valid() && empty($this->dirStack)) {
			$this->observer->onEnd($this);
			return false;
		}
		
		if(!$this->currentDir->valid()) {
			#echo "Sending ".$this->currentDir->getPath()." to observer.".PHP_EOL;
			$this->observer->onFiles($this, $this->currentDir->getPath(),  $this->currentFiles);
			$next = array_shift($this->dirStack);
			#echo "Got ".$next." from stack".PHP_EOL;
			$this->currentFiles = new \Files();
			$this->currentDir = new \DirectoryIterator($next);
		return true;
		}
		$object = $this->currentDir->current();
		$info = $this->getSplFileInfo($object->getFileInfo());
			if($object->getBasename()==="." or $object->getBasename()==="..") {
				$this->currentDir->next();
			return true;
			}
			$this->processed++;
			$realPath = $object->getRealPath();
			if(!$this->inex->isValid($realPath)) {
				$this->currentDir->next();
				return true;
			}
			// Links are ignored for the moment.
			if($object->isLink()) {
				#echo "Link: ".PHP_EOL;
				#echo "\tPath:   ".$info->getPath()."/".$info->getFilename().PHP_EOL;
				#echo "\tTarget: ".$info->getLinkTarget().PHP_EOL;
				#echo "\t: ".$info->getRealPath().PHP_EOL;
				$this->currentDir->next();
				return true;
			}
			try {
				#echo $realPath.PHP_EOL;
				$file = \File::fromPath($realPath);
				$this->currentFiles->addEntry($file);
			} catch(\Exception $e) {
				echo $e::class.PHP_EOL;
				echo $e->getMessage().PHP_EOL;
			}
			
			if(isset($this->processedDirs[$realPath])) {
				echo "Skipping ".$realPath.", already processed".PHP_EOL;
				$this->currentDir->next();
			return true;
			}
			
			if($object->isDir() && $object->getPath()!=="") {
				#echo "Adding ".$realPath." to stack".PHP_EOL;
				$this->dirStack[] = $realPath;
			}
			$this->currentDir->next();
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
		
	}
}