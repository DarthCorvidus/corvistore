<?php
namespace Node;
use plibv4\process\Task;
class WalkDirectory implements Task {
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
	function __construct(string $path, \InEx $inex) {
		$this->path = $path;
		$this->dirStack[] = $this->path;
		$this->inex = $inex;
		$this->inex = new \InEx();
		$this->inex->addExclude("/proc");
		$this->inex->addExclude("/dev");
		$this->inex->addExclude("/sys");

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
		if(empty($this->dirStack)) {
			return false;
		}
		$next = array_shift($this->dirStack);
		$files = new \Files();
		$iterator = new \DirectoryIterator($next);
		foreach($iterator as $object) {
			if($object->getBasename()==="." or $object->getBasename()==="..") {
				continue;
			}
			$this->processed++;
			$realPath = $object->getRealPath();
			if(!$this->inex->isValid($realPath)) {
				continue;
			}
			if($object->isLink()) {
				continue;
			}
			try {
				$file = \File::fromPath($object->getPath());
				$files->addEntry($file);
			} catch(\Exception $e) {
				echo $e::class.PHP_EOL;
				echo $e->getMessage().PHP_EOL;
			}
			if($object->isDir() && $object->getPath()!=="") {
				$this->dirStack[] = $realPath;
			}
		}
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