<?php
class ModeNode implements Mode{
	private $pdo;
	private $catalog;
	private $quit = FALSE;
	function __construct(EPDO $pdo, string $node) {
		$this->pdo = $pdo;
		$this->node = Node::fromName($this->pdo, $node);
	}
	public function onServerMessage(string $message) {
		if(strtoupper($message)=="QUIT") {
			$this->quit = TRUE;
		}
	}
	
	public function isQuit(): bool {
		return $this->quit;
	}
}
