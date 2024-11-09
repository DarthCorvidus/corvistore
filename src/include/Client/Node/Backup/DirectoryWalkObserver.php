<?php
namespace Node;
use plibv4\process\Task;
interface DirectoryWalkObserver {
	function onFiles(Task $task, string $dir, \Files $files): void;
	function onEnd(Task $task): void;
}