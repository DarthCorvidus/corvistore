#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
include __DIR__."/median/RecurseDirectoryGeneric.php";
include __DIR__."/median/RecurseDirectoryObserver.php";

use plibv4\process\Timeshare;
use plibv4\process\Task;
use Node\RecurseDirectoryObserver;
class Median implements RecurseDirectoryObserver {
	private Timeshare $timeshare;
	private Node\RecurseDirectoryGeneric $directory;
	private InEx $inex;
	private array $sizes = array();
	function __construct(string $path) {
		$this->timeshare = new Timeshare();
		$this->inex = new InEx();
		$this->inex->addExclude("/sys");
		$this->inex->addExclude("/run");
		$this->inex->addExclude("/proc");
		$this->inex->addExclude("/dev");
		$this->inex->addExclude("/virtual");
		$this->directory = new Node\RecurseDirectoryGeneric($path, $this->inex, $this);
		$this->timeshare->addTask($this->directory);
	}
	
	function run(): void {
		$this->timeshare->run();
		$middle = (int)round(count($this->sizes)/2);
		sort($this->sizes);
		$unique = array_values(array_unique($this->sizes));
		echo "Median : ".$this->sizes[$middle].PHP_EOL;
		echo "Average: ".(array_sum($this->sizes)/count($this->sizes)).PHP_EOL;
		//print_r($this->sizes);
	}

	public function onEnd(Task $task): void {
		
	}

	public function onFiles(Task $task, string $dir, \Files $files): void {
		
	}

	public function onFile(string $path): void {
		$size = filesize($path);
		if($size === 0) {
			return;
		}
		$this->sizes[] = $size;
	}
}

$median = new Median("/");
$median->run();
