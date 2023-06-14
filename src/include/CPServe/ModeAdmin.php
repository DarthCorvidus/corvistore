<?php
class ModeAdmin implements Mode {
	private $pdo;
	private $quit = FALSE;
	function __construct(EPDO $pdo) {
		$this->pdo = $pdo;
	}
	public function onServerMessage(string $message) {
		$command = new CommandParser($message);
		if($command->getCommand()=="query") {
			$query = new QueryHandler($this->pdo, $command);
			$query->run();
		}
		if($command->getCommand()=="define") {
			$query = new DefineHandler($this->pdo, $command);
			$query->run();
		}
		if($command->getCommand()=="update") {
			$query = new UpdateHandler($this->pdo, $command);
			$query->run();
		}
		
		if($command->getCommand()=="quit") {
			$this->quit = TRUE;
		}
	}
	
	public function isQuit(): bool {
		return $this->quit;
	}
}
