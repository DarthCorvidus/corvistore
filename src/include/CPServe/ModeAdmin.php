<?php
class ModeAdmin implements \Net\ProtocolListener {
	private $pdo;
	private $quit = FALSE;
	function __construct(EPDO $pdo) {
		$this->pdo = $pdo;
	}
	public function onServerMessage(string $message) {
	}
	
	public function isQuit(): bool {
		return $this->quit;
	}

	public function onCommand(string $cmd, \Net\Protocol $protocol) {
		$command = new CommandParser($cmd);
		try {
			if($command->getCommand()=="query") {
				$query = new QueryHandler($this->pdo, $command);
				$protocol->sendMessage($query->run());
			return;
			}
			if($command->getCommand()=="define") {
				$define = new DefineHandler($this->pdo, $command);
				$protocol->sendMessage($define->run());
			return;
			}
			if($command->getCommand()=="update") {
				$update = new UpdateHandler($this->pdo, $command);
				$protocol->sendMessage($update->run());
			return;
			}
			$protocol->sendMessage("'".$cmd."' is not a valid command.".PHP_EOL);
		} catch (Exception $e) {
			$protocol->sendMessage($e->getMessage().PHP_EOL);
		}
	}

	public function onStructuredData(string $data) {
		
	}

	public function onQuit() {
		
	}

}
