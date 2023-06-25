<?php
class CommandHandler {
	private $command;
	private $pdo;
	function __construct(EPDO $pdo, CommandParser $command) {
		$this->pdo = $pdo;
		$this->command = $command;
	}
	
	function execute() {
		if($this->command->getCommand()=="query") {
			$query = new QueryHandler($this->pdo, $this->command);
			return $query->run();
		}
		if($this->command->getCommand()=="define") {
			$define = new DefineHandler($this->pdo, $this->command);
			return $define->run();
		}
		if($this->command->getCommand()=="update") {
			$update = new UpdateHandler($this->pdo, $this->command);
			return $update->run();
		}
	throw new RuntimeException("'".$cmd."' is not a valid command.");
	}
}