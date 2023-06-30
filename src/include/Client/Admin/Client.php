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
		$socket = @stream_socket_client($this->config->getHost().":4096", $errno, $errstr, NULL, STREAM_CLIENT_CONNECT);
		if($socket===FALSE) {
			throw new \RuntimeException("Unable to connect to ".$this->config->getHost().":4096: ".$errstr.".");
		}
		echo "Username: ";
		$user = trim(fgets(STDIN));
		echo "Password: ";
		$password = trim(fgets(STDIN));
		fwrite($socket, "admin ".$user.":".$password."\n");
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
