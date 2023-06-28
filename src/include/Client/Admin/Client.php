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
		echo "Username: ";
		$user = trim(fgets(STDIN));
		echo "Password: ";
		$password = trim(fgets(STDIN));
		socket_write($socket, "admin ".$user.":".$password."\n");
		$this->protocol = new \Net\Protocol($socket);
		$this->protocol->getOK();
	}
	
	function run() {
		while(TRUE) {
			$input = trim(readline("cpadm> "));
			readline_add_history($input);
			if($input=="") {
				continue;
			}

			if($input=="quit") {
				$this->protocol->sendCommand("QUIT");
				return;
			}
			$this->protocol->sendCommand($input);
			echo $this->protocol->getMessage();
		}
	}
}
