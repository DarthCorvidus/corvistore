<?php
class InputListener implements \Net\HubClientListener {
	private $protocol;
	function __construct(\Net\ProtocolReactive $protocol) {
		$this->protocol = $protocol;
	}
	public function getBinary(string $name, int $id): bool {
		return FALSE;
	}

	public function getPacketLength(string $name, int $id): int {
		return 1024;
	}

	public function hasWrite(string $name, int $id): bool {
		return FALSE;
	}

	public function onDisconnect(string $name, int $id) {
		
	}

	public function onRead(string $name, int $id, string $data) {
		$this->protocol->sendCommand($data);
		if($data=="quit") {
			exit();
		}
		if($data=="srv") {
			$this->protocol->expect(\Net\ProtocolReactive::SERIALIZED_PHP);
		return;
		}
		$this->protocol->expect(\Net\ProtocolReactive::MESSAGE);
	}

	public function onWrite(string $name, int $id): string {
		
	}

}
