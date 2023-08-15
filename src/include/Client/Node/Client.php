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
	private $hub;
	private $protocol;
	function __construct($argv) {
	$this->config = new \Client\Config("/etc/crow-protect/client.conf");
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
		$context = new \Net\SSLContext();
		$context->setCAFile("/etc/crow-protect/ca.crt");
		
		$socket = stream_socket_client("ssl://".$this->config->getHost().":4096", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context->getContextClient());
		if($socket===FALSE) {
			throw new \RuntimeException("Unable to connect to ".$this->config->getHost().":4096: ".$errstr.".");
		}
		$this->protocol = new \Net\ProtocolReactive(new ReportListener());
		$this->hub = new \StreamHub();
		
		
		$this->hub->addClientStream("ssl", 0, $socket, $this->protocol);
		$this->protocol->sendCommand("mode node");
		$this->protocol->sendCommand("authenticate ".$this->config->getNode().":".file_get_contents("/root/.crow-protect"));
		$this->protocol->expect(\Net\ProtocolReactive::OK);
	}
	
	function run() {
		$this->hub->listen();
	return;
		#if($this->argv[1]=="test") {
		#	$backup = new Test($this->config, $this->argv);
		#	$backup->run();
		#	return;
		#}
		/*
		 * I don't want PHP to throw E_WARNings around, so I use @ to silence
		 * it and do proper error handling afterwards.
		 */
		
		
		#$socket = @stream_socket_client($this->config->getHost().":4096", $errno, $errstr, NULL, STREAM_CLIENT_CONNECT);
		if($socket===FALSE) {
			throw new \RuntimeException("Unable to connect to ".$this->config->getHost().":4096: ".$errstr.".");
		}
		fwrite($socket, "node ".$this->config->getNode().":".file_get_contents("/root/.crow-protect")."\n");
		$protocol = new \Net\Protocol($socket);
		$protocol->getOK();

		
		if($this->argv[1]=="backup") {
			#$backup = new Backup($protocol, $this->config, $this->argv);
			#$backup->run();
		}

		if($this->argv[1]=="report") {
			#$backup = new Report($protocol, $this->config, $this->argv);
			#$backup->run();
		}

		if($this->argv[1]=="restore") {
			#$backup = new Restore($protocol, $this->config, $this->argv);
			#$backup->run();
		}
	}
}
