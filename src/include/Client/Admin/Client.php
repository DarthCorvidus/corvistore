<?php
namespace Admin;
/**
 * Core class for cpclient.php, which does backup, restore and report.
 *
 * @author Claus-Christoph Küthe
 */


class Client {
	private $config;
	private $argv;
	private $hub;
	private $protocol;
	function __construct($argv) {
		$this->hub = new \StreamHub();
		$this->config = new \Client\Config("/etc/crow-protect/client.conf");
		$this->argv = new \ArgvAdmin($argv);
		$context = new \Net\SSLContext();
		$context->setCAFile("/etc/crow-protect/ca.crt");
		#$socket = stream_socket_client("ssl://".$this->config->getHost().":4096", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context->getContextClient());
		$socket = stream_socket_client("unix://".\Shared::getIPCSocket(), $errno, $errstr, STREAM_CLIENT_CONNECT);

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
		$this->protocol = new \Net\ProtocolReactive(new ProtocolListener());
		$this->inputListener = new InputListener($this->protocol);
		$this->hub->addClientStream("ssl", 0, $socket, $this->protocol);
		$this->hub->addClientStream("input", 0, STDIN, $this->inputListener);
		$this->protocol->sendCommand("mode admin");
		#$this->protocol->expect(\Net\Protocol::OK);
		$this->protocol->sendCommand("authenticate ".$user.":".$password);
		#$this->protocol->expect(\Net\Protocol::OK);
	}
	
	function run() {
		$this->hub->listen();
	}
}
