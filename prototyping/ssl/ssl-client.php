#!/usr/bin/php
<?php
class Client {
	private $socket;
	function __construct() {
		$this->socket = stream_socket_client("tcp://0.0.0.0:4096", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
		stream_set_blocking($this->socket, TRUE);
	}
	
	function run() {
		while(TRUE) {
			echo "> ";
			$input = trim(fgets(STDIN));
			echo "Sending ".$input.PHP_EOL;
			fwrite($this->socket, $input.PHP_EOL);
			$answer = fgets($this->socket);
			echo $answer.PHP_EOL;
			if($input=="quit") {
				fclose($this->socket);
				break;
			}
		}
	}
}

$client = new Client();
$client->run();