<?php
namespace Node;
/**
 * Core class for cpclient.php, which does backup, restore and report.
 *
 * @author Claus-Christoph Küthe
 */


class Client {
	private $pdo;
	private $config;
	private $password;
	function __construct($argv) {
	$this->config = new \Client\Config("/etc/crow-protect/client.yml");
		$this->argv = $argv;
		if(!isset($this->argv[1]) or !in_array($this->argv[1], array("restore", "backup", "report", "test"))) {
			throw new \Exception("Please select operation mode: restore, backup, report, test");
		}
		$pwfile = "/root/.crow-protect";
		if(!file_exists($pwfile)) {
			echo "Please enter password: ";
			$password = fgets(STDIN);
			file_put_contents($pwfile, trim($password));
			chmod($pwfile, 0600);
		}
	}
	
	function run() {
		if($this->argv[1]=="test") {
			$backup = new Test($this->config, $this->argv);
			$backup->run();
			return;
		}
		
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(@socket_connect($socket, $this->config->getHost(), 4096)===FALSE) {
			throw new \RuntimeException(sprintf("Socket connect with %s:%d failed: %s", $this->config->getHost(), 4096, socket_strerror(socket_last_error())));	
		}
		socket_write($socket, "node ".$this->config->getNode().":".file_get_contents("/root/.crow-protect")."\n");
		$protocol = new \Net\Protocol($socket);
		$protocol->getOK();

		
		if($this->argv[1]=="backup") {
			$backup = new Backup($protocol, $this->config, $this->argv);
			$backup->run();
		}

		if($this->argv[1]=="report") {
			$backup = new Report($protocol, $this->config, $this->argv);
			$backup->run();
		}

		if($this->argv[1]=="restore") {
			$backup = new Restore($protocol, $this->config, $this->argv);
			$backup->run();
		}
	}
}
