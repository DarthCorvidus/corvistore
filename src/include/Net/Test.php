<?php
namespace Net;
class Test {
	private $config;
	private $argv;
	private $socket;
	private $protocol;
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	function __construct(Config $config, array $argv) {
		$this->config = $config;
		$this->argv = $argv;
		$this->inex = $config->getInEx();
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_connect($this->socket, '127.0.0.1', 4096);
		socket_write($this->socket, "test\n");
		$this->protocol = new Protocol($this->socket);
		#$this->inex = new InEx();
		#$this->inex->addInclude("/boot/");
		#$this->inex->addInclude("/tmp/");
		#$this->inex->addInclude("/var/log/");
		#$this->inex->addInclude("/home/hm/kernel/");
	}
	
	function readData() {
		$length = \IntVal::uint32LE()->getValue(socket_read($this->socket, 4));
		#echo "Reading ".number_format($length)." bytes of Data.".PHP_EOL;
		if($length<=4096) {
			$data = socket_read($this->socket, $length);
			#echo "Got ".strlen($data).PHP_EOL;
			return $data;
		}
		$rest = $length;
		$data = "";
		while($rest>4096) {
			$data .= socket_read($this->socket, 4096);
			$rest -= 4096;
		}$data .= socket_read($this->socket, $rest);
		#echo "Got ".number_format(strlen($data)).PHP_EOL;
	return $data;
	}
	
	function run() {
		$test = array();
		for($i=0;$i<2500;$i++) {
			$test[] = sha1($i.time());
		}
		#$serialize = serialize($test);
		#$serlen = strlen($serialize);
		#echo "Serialized length: ".number_format($serlen).PHP_EOL;
		#socket_write($this->socket, \IntVal::uint8()->putValue(\Net\Protocol::SERIAL_PHP));
		#socket_write($this->socket, \IntVal::uint32LE()->putValue($serlen));
		#socket_write($this->socket, $serialize);
		#echo "Quitting...";
		echo "Requesting date from server:".PHP_EOL;
		$this->protocol->sendCommand("DATE");
		echo " ".$this->protocol->getMessage().PHP_EOL;
		echo "Requesting wrong result from server:".PHP_EOL;
		try {
			$this->protocol->sendCommand("FAKE");
			echo " ".$this->protocol->getMessage().PHP_EOL;
		} catch (\Exception $e) {
			echo " ".$e->getMessage().PHP_EOL;
		}
		
		$this->protocol->sendCommand("QUIT");
		echo "Done.".PHP_EOL;
	}
}
