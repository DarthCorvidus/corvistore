<?php
namespace Node;
use plibv4\process\Task;
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
		$this->inex->addExclude("/proc");
		$this->inex->addExclude("/dev");
		$this->inex->addExclude("/sys");
		$this->currentFiles = new \Files();
		$this->currentDir = new \DirectoryIterator($this->path);
		$this->observer = $observer;
	}

	public function __tsError(\Exception $e, int $step): void {
		echo $e::class.PHP_EOL;
		print $e->getTraceAsString().PHP_EOL;
		throw $e;
	}

	public function __tsFinish(): void {
		echo "Processed: ".$this->processed.PHP_EOL;
	}

	public function __tsKill(): void {
		
	}

	public function __tsLoop(): bool {
		if(!$this->currentDir->valid() && empty($this->dirStack)) {
			$this->observer->onEnd($this);
			return false;
		}
		
		if(!$this->currentDir->valid()) {
			$this->observer->onFiles($this, $this->currentDir->getPath(),  $this->currentFiles);
			$next = array_shift($this->dirStack);
			$this->currentFiles = new \Files();
			$this->currentDir = new \DirectoryIterator($next);
		}
		$object = $this->currentDir->current();
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
			if($object->isLink()) {
				$this->currentDir->next();
				return true;
			}
			try {
				$file = \File::fromPath($object->getPath());
				$this->currentFiles->addEntry($file);
			} catch(\Exception $e) {
				echo $e::class.PHP_EOL;
				echo $e->getMessage().PHP_EOL;
			}
			if($object->isDir() && $object->getPath()!=="") {
				$this->dirStack[] = $realPath;
			}
			$this->currentDir->next();
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