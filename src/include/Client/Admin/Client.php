<?php
namespace Admin;
/**
 * Core class for cpclient.php, which does backup, restore and report.
 *
 * @author Claus-Christoph Küthe
 */


class Client {
	private $config;
	private $protocol;
	function __construct($argv) {
		$this->config = new \Client\Config("/etc/crow-protect/client.yml");
		$this->argv = $argv;
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$result = socket_connect($socket, $this->config->getHost(), 4096);
		if($result===FALSE) {
			exit(255);
		}
		socket_write($socket, "admin ".$this->config->getNode()."\n");
		$this->protocol = new \Net\Protocol($socket);

	}
	
	function run() {
		while(TRUE) {
			echo "> ";
			$input = trim(fgets(STDIN));
			if($input=="quit") {
				$this->protocol->sendCommand("QUIT");
				return;
			}
			$this->protocol->sendCommand($input);
			echo $this->protocol->getMessage();
		}
	}
}
