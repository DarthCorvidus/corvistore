#!/usr/bin/php
<?php
include __DIR__."/../../vendor/autoload.php";
class Client implements StreamHubListener {
	private $socket;
	private $protocol;
	private $hub;
	function __construct($server) {
		$this->hub = new StreamHub();
		$this->hub->addCustomStream("input", STDIN);
		$this->hub->addStreamHubListener("input", $this);
		#$context = stream_context_create();
		#stream_context_set_option($context, 'ssl', 'local_ca', __DIR__."/ca.crt");
		##stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
		#stream_context_set_option($context, 'ssl', 'verify_peer', true);

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
		$this->hub->addCustomStream("ssl", $this->socket);
		$this->hub->addStreamHubListener("ssl", $this);
		$this->protocol = new \Net\ProtocolBase($this->socket);
	}
	
	function run() {
		$this->hub->listen();
		
		/*
		echo $this->protocol->getMessage().PHP_EOL;
		while(TRUE) {
			echo "> ";
			$input = trim(fgets(STDIN));
			echo "Sending ".$input.PHP_EOL;
			$this->protocol->sendCommand($input);
			echo $this->protocol->getMessage().PHP_EOL;
			#if($input=="sleep") {
			#	echo $this->protocol->getMessage().PHP_EOL;
			#}
			if($input=="count") {
				for($i=0;$i<24;$i++) {
					echo $this->protocol->getMessage().PHP_EOL;
				}
			}
			if($input=="quit") {
				fclose($this->socket);
				break;
			}
		}
		 * 
		 */
	}

	public function onRead(string $name, int $id, $stream) {
		if($name=="input") {
			$input = trim(fgets($stream));
			echo "User typed: ".$input.PHP_EOL;
			$this->protocol->sendCommand($input);
			if($input=="quit") {
				$this->hub->close("ssl", 0);
				exit();
			}
		}
		
		
		if($name=="ssl") {
			echo "Server sent: ".$this->protocol->getMessage().PHP_EOL;
		}
		
	}

	public function onWrite(string $name, int $id, $stream) {
		
	}

	public function onConnect(string $name, int $id, $newClient) {
		
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