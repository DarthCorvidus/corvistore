<?php
class ModeTest implements \Net\ProtocolListener {
	private $clientId;
	function __construct(int $clientId) {
		$this->clientId = $id;
	}
	public function onCommand(string $string) {
		echo "Received command in mode test: ".$string.PHP_EOL;
	}

	public function onQuit() {
		echo "Client ".$this->clientId." requested quit. Ending connection.";
	}

	public function onSerializedPHP(string $data) {
		$unser = unserialize($data);
		echo "Got structured data: ".gettype($unser).PHP_EOL;
	}

}