<?php
namespace Admin;
class ProtocolListener implements \Net\ProtocolAsyncListener {
	public function onCommand(\Net\ProtocolAsync $protocol, string $command): void {
		
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol): void {
		echo "Lost connection to server".PHP_EOL;
		exit();
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message): void {
		echo $message.PHP_EOL;
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, mixed $unserialized): void {
		echo "Serialized data".PHP_EOL;
	}

	public function onOk(\Net\ProtocolAsync $protocol): void {
		
	}

	public function onBinaryClass(\Net\ProtocolAsync $protocol, string $classname, string $classdata): void {
		throw new \RuntimeException("not implemented");
	}

	public function onStreamEnd(\Net\ProtocolAsync $protocol, \Net\StreamReceiver $streamReceiver): void {
		throw new \RuntimeException("no file stream expected in ".self::class);
	}

	public function onStreamStart(\Net\ProtocolAsync $protocol, \Net\StreamReceiver $streamReceiver): void {
		throw new \RuntimeException("no file stream expected in ".self::class);
	}
}