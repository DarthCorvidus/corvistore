<?php
class ModeTest implements \Net\ProtocolListener {
	private $clientId;
	function __construct(int $clientId) {
		$this->clientId = $clientId;
	}
	public function onCommand(string $command, \Net\Protocol $protocol) {
		echo "Received command in mode test: ".$command.PHP_EOL;
		if($command=="SEND OK") {
			$protocol->sendOK();
		return;
		}
		if($command=="DATE") {
			echo date("Y-m-d H:i:sP").PHP_EOL;
			$protocol->sendMessage(date("Y-m-d H:i:sP"));
			return;
		}
		if($command=="SEND RAW") {
			$file = "/tmp/crowfile.".$this->clientId.".bin";
			\Net\Test::createRandom($file, 25, 112);
			echo " Sent: ".md5_file($file).PHP_EOL;
			$reread = fopen($file, "r");
			$protocol->sendRaw(filesize($file), $reread);
			fclose($reread);
		return;
		}
		
		if($command=="RECEIVE RAW") {
			try {
				$filename = "/tmp/crowclient.".$this->clientId.".bin";
				$handle = fopen($filename, "w");
				$protocol->getRaw($handle);
				fclose($handle);
				echo " Received: ".md5_file($filename).PHP_EOL;
			} catch (\Net\CancelException $e) {
				echo " Upload aborted, recoverable error.".PHP_EOL;
				fclose($handle);
				unlink($filename);
			}
			
			return;
		}

		if($command=="SEND STRUCTURED PHP") {
			$array = array();
			for($i=0;$i<2500;$i++) {
				$array[] = random_bytes(64);
			}
			echo " Array count: ".count($array).PHP_EOL;
			echo " Value 1000:  ".md5($array[1000]).PHP_EOL;
			$protocol->sendSerializePHP($array);
		return;
		}
		
	$protocol->sendError("Command ".$command." not known.");
	}

	public function onQuit() {
		echo "Client ".$this->clientId." requested quit. Ending connection.".PHP_EOL;
	}
}