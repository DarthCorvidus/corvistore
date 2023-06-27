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
		$handler = new CommandHandler($this->pdo, $command);
		try {
			$result = $handler->execute();
			$protocol->sendMessage($result);
		} catch (Exception $ex) {
			$protocol->sendMessage($ex->getMessage());
		}
	}

	public function onStructuredData(string $data) {
		
	}

	public function onQuit() {
		
	}

}
