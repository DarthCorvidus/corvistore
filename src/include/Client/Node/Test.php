<?php
namespace Node;
class Test implements \Net\TransferListener {
	private $config;
	private $argv;
	private $socket;
	private $protocol;
	private $transfer;
	private $filename;
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	function __construct(\Client\Config $config, array $argv) {
		$this->config = $config;
		$this->argv = $argv;
		$this->inex = $config->getInEx();

		$context = new \Net\SSLContext();
		$context->setCAFile("/etc/crow-protect/ca.crt");

		$this->socket = stream_socket_client("ssl://".$this->config->getHost().":4096", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context->getContextClient());
		fwrite($this->socket, "test\n");
		#stream_set_blocking($this->socket, TRUE);
		$this->protocol = new \Net\Protocol($this->socket);
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
	
	static function createRandom(string $filename, int $blocks, int $rest) {
		$read = fopen("/dev/urandom", "r");
		$write = fopen($filename, "w");
		for($i=0;$i<$blocks;$i++) {
			fwrite($write, fread($read, 4096));
		}
		fwrite($write, fread($read, $rest));
		fclose($write);
		fclose($read);
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
		$this->protocol->getOK();
		echo "Requesting OK from server: ".PHP_EOL;
		$this->protocol->sendCommand("SEND OK");
		$this->protocol->getOK();
		
		echo "Requesting DATE from server:".PHP_EOL;
		$this->protocol->sendCommand("DATE");
		echo " ".$this->protocol->getMessage().PHP_EOL;
		echo "Requesting wrong result from server:".PHP_EOL;
		try {
			$this->protocol->sendCommand("FAKE");
			echo " ".$this->protocol->getMessage().PHP_EOL;
		} catch (\Exception $e) {
			echo " ".$e->getMessage().PHP_EOL;
		}
		
		echo "Requesting SEND STRUCTURED PHP from server:".PHP_EOL;
		$this->protocol->sendCommand("SEND STRUCTURED PHP");
		$unserialized = $this->protocol->getUnserializePHP();
		echo " Array count: ".count($unserialized).PHP_EOL;
		echo " Value 1000 : ".md5($unserialized[1000]).PHP_EOL;
		
		echo "Requesting RAW data from server: ".PHP_EOL;
		$this->protocol->sendCommand("SEND RAW");
		$this->filename = __DIR__."/test.bin";
		$this->protocol->getRaw($this);
		#echo " Size:     ".number_format(files).PHP_EOL;
		#echo " File md5: ".md5_file(__DIR__."/test.bin").PHP_EOL;

		echo "Sending RAW data to server: ".PHP_EOL;
		$this->protocol->sendCommand("RECEIVE RAW");
		$send = __DIR__."/client.bin";
		self::createRandom($send, 25, 674);
		echo " md5sum: ".md5_file($send).PHP_EOL;
		$fh = fopen($send, "r");
		$this->protocol->sendRaw(filesize($send), $fh);
		fclose($fh);
		
		if(isset($this->argv[2])) {
			echo "Sending FILE to server: ".PHP_EOL;
			$file = \File::fromPath($this->argv[2]);
			echo " md5sum: ".md5_file($file->getPath()).PHP_EOL;
			for($i=0;$i<5;$i++) {
				try {
					echo "Try ".($i+1)." out of 5: ";
					$this->protocol->sendCommand("RECEIVE RAW");
					$this->protocol->sendFile($file);
					echo "Upload complete.".PHP_EOL;
					break;
				} catch (\Net\UploadException $e) {
					echo "Upload failed.".PHP_EOL;
				}
			}
		}

		
		$this->protocol->sendCommand("QUIT");
		echo "Done.".PHP_EOL;
	}

	public function onCancel() {
		fclose($this->handle);
		unlink($this->filename);
	}

	public function onData(string $data) {
		fwrite($this->transfer, $data);
	}

	public function onEnd() {
		fclose($this->transfer);
		echo " Size:     ".number_format(filesize($this->filename)).PHP_EOL;
		echo " File md5: ".md5_file($this->filename).PHP_EOL;
	}

	public function onFail() {
		fclose($this->handle);
		unlink($this->filename);
	}

	public function onStart(int $size) {
		$this->transfer = fopen($this->filename, "w");
	}

}
