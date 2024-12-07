<?php
class CPServe {
	/**
	 * due to the init command, the database cannot be set in the constructor,
	 * as it does not yet exist if init is called.
	 * @psalm-suppress PropertyNotSetInConstructor
	 * @var \EPDO
	 */
	private \EPDO $pdo;
	private \ArgvServe $arg;
	function __construct(array $argv) {
		$user = posix_getuid();
		$group = posix_getgid();
		if($user===0 or $group===0) {
			throw new RuntimeException("cpserve.php is not supposed to run as root.");
		}
		$this->arg = new ArgvServe($argv);
	}

	function runCommand(): void {
		echo $this->arg->getRun().PHP_EOL;
		$command = new CommandParser($this->arg->getRun());
		$handler = new CommandHandler($this->pdo, $command);
		echo $handler->execute();
		echo PHP_EOL;
	}
	
	function runFile(): void {
		$commands = file($this->arg->getRunFile());
		foreach($commands as $cmd) {
			echo $cmd;
			$command = new CommandParser(trim($cmd));
			$handler = new CommandHandler($this->pdo, $command);
			echo $handler->execute();
			echo PHP_EOL;
		}
	}

	function run(): void {
		if($this->arg->hasInit()) {
			$init = new Init($this->arg);
			$init->run();
		return;
		}
		$this->pdo = Shared::getEPDO();
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
