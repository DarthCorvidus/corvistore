<?php
namespace Node;
/**
 * Core class for cpclient.php, which does backup, restore and report.
 *
 * @author Claus-Christoph Küthe
 */


class Client {
private \Client\Config $config;
	private \Net\ProtocolSync $protocol;
	private mixed $socket;
	/** @var list<string> */
	private array $argv;
	/**
	 * 
	 * @param list<string> $argv As initialized by PHP when run from CLI
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	function __construct(array $argv) {
		$user = posix_getuid();
		$group = posix_getgid();
		if($user!==0 or $group!==0) {
			throw new \RuntimeException("cpnc.php must be run as root.");
		}

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
		
		$this->socket = stream_socket_client("ssl://".$this->config->getHost().":4096", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context->getContextClient());
		if($this->socket===FALSE) {
			throw new \RuntimeException("Unable to connect to ".$this->config->getHost().":4096: ".$errstr.".");
		}
		#$this->hub = new \StreamHub();
		
		#if($argv[1]=="report") {
		#	$this->protocol = new \Net\ProtocolReactive(new ReportListener($argv));
		#}

		#if($argv[1]=="backup") {
		#	$this->protocol = new \Net\ProtocolReactive(new BackupListener($this->config, $argv));
		#}
		$this->protocol = new \Net\ProtocolSync(new \Net\StreamClient($this->socket));
		#$this->hub->addClientStream("ssl", 0, $socket);
		#$this->hub->addClientListener("ssl", 0, $this->protocol);
		$this->protocol->sendCommand("mode node");
		$this->protocol->sendCommand("authenticate ".$this->config->getNode().":".trim(file_get_contents("/root/.crow-protect")));
		#$this->protocol->expect(\Net\ProtocolReactive::OK);
		$this->protocol->getOK();
	}
	
	function run(): void {
		if($this->argv[1]=="backup") {
			$backup = new Backup($this->socket, $this->config, $this->argv);
			$backup->run();
		}
	
		if($this->argv[1]=="restore") {
			$backup = new Restore($this->socket, $this->config, $this->argv);
			$backup->run();
		}

		if($this->argv[1]=="report") {
			$backup = new Report($this->protocol, $this->config, $this->argv);
			$backup->run();
		}
	}
}
