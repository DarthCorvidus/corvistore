#!/usr/bin/php
<?php
include __DIR__."/../../vendor/autoload.php";
class Client {
	private $socket;
	private $protocol;
	function __construct($server) {
		$context = stream_context_create();
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
		if($this->socket===FALSE) {
			exit(255);
		}
		
		$this->protocol = new \Net\ProtocolBase($this->socket);
	}
	
	function run() {
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