<?php
class ClientProtocolListener implements \Net\ProtocolReactiveListener {
	public function onCommand(string $command) {
		
	}

	public function onDisconnect() {
		echo "Lost connection to server".PHP_EOL;
		exit();
	}

	public function onMessage(string $message) {
		echo $message.PHP_EOL;
	}

}
