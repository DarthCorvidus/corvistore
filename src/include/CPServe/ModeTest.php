<?php
class ModeTest implements \Net\ProtocolListener {
	private $clientId;
	private $protocol;
	function __construct(int $clientId) {
		$this->clientId = $clientId;
	}
	public function onCommand(string $command, \Net\Protocol $protocol) {
		echo "Received command in mode test: ".$command.PHP_EOL;
		if($command=="DATE") {
			echo date("Y-m-d H:i:sP").PHP_EOL;
			$protocol->sendMessage(date("Y-m-d H:i:sP"));
			return;
		}
		if($command=="SEND FILE") {
			$file = "/tmp/crowfile.".$this->clientId.".bin";
			$read = fopen("/dev/urandom", "r");
			$write = fopen($file, "w");
			for($i=0;$i<10;$i++) {
				fwrite($write, fread($read, 4096));
			}
			fwrite($write, fread($read, 386));
			
			fclose($write);
			fclose($read);
			echo "md5sum: ".md5_file($file).PHP_EOL;
			$reread = fopen($file, "r");
			$protocol->sendRaw(filesize($file), $reread);
			fclose($reread);
		return;
		}
	$protocol->sendError("Command ".$command." not known.");
	}

	public function onQuit() {
		echo "Client ".$this->clientId." requested quit. Ending connection.".PHP_EOL;
	}

	public function onSerializedPHP(string $data, \Net\Protocol $protocol) {
		$unser = unserialize($data);
		echo "Got structured data: ".gettype($unser).PHP_EOL;
	}

}