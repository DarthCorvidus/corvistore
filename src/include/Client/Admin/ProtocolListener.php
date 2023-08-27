<?php
namespace Admin;
class ProtocolListener implements \Net\ProtocolAsyncListener {
	public function onCommand(\Net\ProtocolAsync $protocol, string $command) {
		
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol) {
		echo "Lost connection to server".PHP_EOL;
		exit();
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message) {
		echo $message.PHP_EOL;
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, $unserialized) {
		echo "Serialized data".PHP_EOL;
		print_r($unserialized);
	}

	public function onOk(\Net\ProtocolAsync $protocol) {
		
	}

}