#!/usr/bin/php
<?php
include __DIR__."/../../vendor/autoload.php";
include __DIR__."/InputListener.php";
include __DIR__."/ClientProtocolListener.php";
class Client {
	private $socket;
	private $protocol;
	private $hub;
	private $input = "";
	private $inputListener;
	function __construct($server) {
		$this->hub = new StreamHub();

		$context = new Net\SSLContext();
		$context->setCAFile(__DIR__."/ca.crt");
		
		$this->socket = stream_socket_client("tcp://".$server.":4096", $errno, $errstr, NULL, STREAM_CLIENT_CONNECT, $context->getContextClient());
		stream_set_blocking($this->socket, TRUE);
		if (! stream_socket_enable_crypto ($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT )) {
			exit();
		}
		stream_set_blocking($this->socket, FALSE);
		if($this->socket===FALSE) {
			exit(255);
		}
		$this->protocol = new \Net\ProtocolReactive(new ClientProtocolListener());
		$this->inputListener = new InputListener($this->protocol);
		$this->hub->addClientStream("ssl", 0, $this->socket, $this->protocol);
		$this->hub->addClientStream("input", 0, STDIN, $this->inputListener);
	}
	
	function run() {
		$this->hub->listen();
	}
}

if(empty($argv[1])) {
	echo "Please suppy host name.".PHP_EOL;
	exit();
}

if(!file_exists(__DIR__."/ca.crt")) {
	echo "Root certificate ".__DIR__."/ca.crt not found".PHP_EOL;
	exit();
}

$client = new Client($argv[1]);
$client->run();