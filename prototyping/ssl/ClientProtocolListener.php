<?php
class ClientProtocolListener implements \Net\ProtocolReactiveListener {
	public function onCommand(\Net\ProtocolReactive $protocol, string $command) {
		
	}

	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		echo "Lost connection to server".PHP_EOL;
		exit();
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $message) {
		echo $message.PHP_EOL;
	}

}
