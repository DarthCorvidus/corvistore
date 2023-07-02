<?php
class CPServe {
	private $pdo;
	function __construct(array $argv) {
		$databasePath = "/var/lib/crow-protect/crow-protect.sqlite";
		$shared = new Shared();
		$shared->useSQLite($databasePath);
		$this->arg = new ArgvServe($argv);
		$this->pdo = $shared->getEPDO();
	}

	function runCommand() {
		echo $this->arg->getRun().PHP_EOL;
		$command = new CommandParser($this->arg->getRun());
		$handler = new CommandHandler($this->pdo, $command);
		echo $handler->execute();
		echo PHP_EOL;
	}
	
	function runFile() {
		$commands = file($this->arg->getRunFile());
		foreach($commands as $cmd) {
			echo $cmd;
			$command = new CommandParser(trim($cmd));
			$handler = new CommandHandler($this->pdo, $command);
			echo $handler->execute();
			echo PHP_EOL;
		}
	}

	function run() {
		if($this->arg->hasRun()) {
			$this->runCommand();
			return;
		}

		if($this->arg->hasRunFile()) {
			$this->runFile();
			return;
		}
		$server = new Server($this->pdo);
		$server->run();
	}
}
