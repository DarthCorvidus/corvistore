<?php
namespace Admin;
class InputListener implements \Net\HubClientListener {
	private $protocol;
	function __construct(\Net\ProtocolReactive $protocol) {
		$this->protocol = $protocol;
	}
	public function getBinary(): bool {
		return FALSE;
	}

	public function getPacketLength(): int {
		return 1024;
	}

	public function hasWrite(): bool {
		return FALSE;
	}

	public function onDisconnect() {
		
	}

	public function onRead(string $data) {
		$quit = (new class() implements \Net\ProtocolSendListener {
			public function onSent(\Net\ProtocolReactive $protocol) { exit(); }}
		);

		if($data=="quit") {
			$this->protocol->sendCommand($data, $quit);
		return;
		}
		$this->protocol->sendCommand($data, $this);
		if($data=="srv") {
			$this->protocol->expect(\Net\ProtocolReactive::SERIALIZED_PHP);
		return;
		}
		$this->protocol->expect(\Net\ProtocolReactive::MESSAGE);
	}

	public function onWrite(): string {
		
	}

	public function onWritten() {
		
	}
}
