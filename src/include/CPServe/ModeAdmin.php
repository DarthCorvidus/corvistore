<?php
class ModeAdmin {
	private $pdo;
	private $quit = FALSE;
	private $user;
	private $protocol;
	function __construct(EPDO $pdo, \Net\ProtocolBase $protocol, string $conjoined) {
		$this->pdo = $pdo;
		$this->user = User::authenticate($this->pdo, $conjoined);
		$this->protocol = $protocol;
		$this->protocol->sendOK();
	}
	public function onServerMessage(string $message) {
	}
	
	public function isQuit(): bool {
		return $this->quit;
	}

	public function run() {
		while(TRUE) {
			$cmd = $this->protocol->getCommand();
			if($cmd=="quit") {
				$this->protocol->sendMessage("Quit on client request.");
				return;
			}
			$command = new CommandParser($cmd);
			if($command->getCommand()=="count") {
				$this->protocol->sendMessage("Counting to 25");
				for($i=0;$i<25;$i++) {
					$this->protocol->sendMessage($i);
					sleep(1);
				}
			continue;
			}
			$handler = new CommandHandler($this->pdo, $command);
			try {
				$result = $handler->execute();
				$this->protocol->sendMessage(trim($result));
			} catch (Exception $ex) {
				$this->protocol->sendMessage($ex->getMessage());
			}
		}
	}
	
	public function onStructuredData(string $data) {
		
	}

	public function onQuit() {
		
	}

}
