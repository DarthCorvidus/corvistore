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
	private $argv;
	function __construct($argv) {
		$this->config = new \Client\Config("/etc/crow-protect/client.conf");
		$this->argv = new \ArgvAdmin($argv);
		$context = new \Net\SSLContext();
		$context->setCAFile("/etc/crow-protect/ca.crt");
		$socket = stream_socket_client("ssl://".$this->config->getHost().":4096", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context->getContextClient());

		#$socket = @stream_socket_client($this->config->getHost().":4096", $errno, $errstr, NULL, STREAM_CLIENT_CONNECT);
		if($socket===FALSE) {
			throw new \RuntimeException("Unable to connect to ".$this->config->getHost().":4096: ".$errstr.".");
		}
		if($this->argv->hasUsername()) {
			$user = $this->argv->getUsername();
		} else {
			echo "Username: ";
			$user = trim(fgets(STDIN));
		}
		if($this->argv->hasPassword()) {
			$password = $this->argv->getPassword();
		} else {
			echo "Password: ";
			$password = trim(fgets(STDIN));
		}
		
		$this->protocol = new \Net\ProtocolBase($socket);
		$this->protocol->sendMessage("admin ".$user.":".$password);
		$this->protocol->getOK();
	}
	
	function run() {
		while(TRUE) {
			
			$input = trim(readline("cpadm> "));
			readline_add_history($input);
			if($input=="") {
				continue;
			}
			$this->protocol->sendCommand($input);
			echo $this->protocol->getMessage().PHP_EOL;
			if($input=="count") {
				for($i=0;$i<25;$i++) {
					echo $this->protocol->getMessage().PHP_EOL;
				}
			continue;
			}
			if($input=="quit") {
				exit();
			}
		}
	}
}
