<?php
namespace Net;
/**
 * Core class for cpclient.php, which does backup, restore and report.
 *
 * @author Claus-Christoph Küthe
 */


class Client {
	private $pdo;
	private $config;
	function __construct($argv) {
		$this->config = new Config("/etc/crow-protect/client.yml");
		$this->argv = $argv;
		if(!isset($this->argv[1]) or !in_array($this->argv[1], array("restore", "backup", "report"))) {
			throw new \Exception("Please select operation mode: restore, backup, report");
		}
		
	}
	
	function run() {
		if($this->argv[1]=="backup") {
			$backup = new Backup($this->config, $this->argv);
			$backup->run();
		}
		/*
		if($this->argv[1]=="restore") {
			$backup = new Restore($this->pdo, $this->config, $this->argv);
			$backup->run();
		}
		
		if($this->argv[1]=="report") {
			$backup = new Report($this->pdo, $this->config, $this->argv);
			$backup->run();
		}
		 * 
		 */
	}
}
