<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\ProtocolAsync;
class ProtocolAsyncTest extends TestCase implements Net\ProtocolAsyncListener, \Net\ProtocolSendListener {
	private $lastString;
	private $lastUnserialized;
	private $lastOK = TRUE;
	private $sent = NULL;
	#const FILESIZE = 93821;
	const FILESIZE = 1024*11;
	function __construct() {
		parent::__construct();
	}
	function setUp() {
		$this->lastString = NULL;
		$this->lastUnserialized = array();
		$this->lastOK = FALSE;
		$this->sent = NULL;
	}
	
	function tearDown() {
		$this->lastString = NULL;
		$this->lastUnserialized;
		$this->lastOK = FALSE;
		$this->sent = NULL;
		if(file_exists(self::getSourceName())) {
			unlink(self::getSourceName());
		}
		if(file_exists(self::getTargetName())) {
			unlink(self::getTargetName());
		}
		foreach(self::getSourceNames() as $value) {
			if(file_exists($value)) {
				unlink($filename);
			}
		}

		foreach(self::getTargetNames() as $value) {
			if(file_exists($value)) {
				unlink($filename);
			}
		}
		
	}
	
	static function getSourceName() {
		return __DIR__."/source.bin";
	}

	static function getSourceNames(): array {
		return array(__DIR__."/source01.bin", __DIR__."/source03.bin", __DIR__."/source.bin");
	}
	
	
	static function getTargetName() {
		return __DIR__."/target.bin";
	}
	
	static function getTargetNames(): array {
		return array(__DIR__."/target01.bin", __DIR__."/target03.bin", __DIR__."/target02.bin");
	}

	function testConstruct() {
		$protocol = new ProtocolAsync($this);
		$this->assertInstanceOf(ProtocolAsync::class, $protocol);
	}
	
	function testGetDefaultSize() {
		$protocol = new ProtocolAsync($this);
		$this->assertEquals(1024, $protocol->getPacketLength());
	}
	
	#function testGetStackSize() {
	#	$protocol = new ProtocolAsync($this);
	#	$string = serialize($_SERVER);
	#	$steps = (int)ceil(strlen($string)/$protocol->getPacketLength(" ", 0));
	#	$protocol->sendMessage($string);
	#	$this->assertEquals($steps, $protocol->getStackSize());
	#}
	
	function testOnWriteShortCommand() {
		$expected = chr(ProtocolAsync::COMMAND).IntVal::uint32LE()->putValue(4)."quit";
		$protocol = new ProtocolAsync($this);
		$protocol->sendCommand("quit");
		$write = $protocol->onWrite();
		$protocol->onWritten();
		$this->assertEquals(1024, strlen($write));
		$this->assertEquals($expected, substr($write, 0, 1+4+4));
	}
	
	function testOnReadShortCommand() {
		$protocol = new ProtocolAsync($this);
		$expected = "quit";
		$data = chr(ProtocolAsync::COMMAND);
		$data .= IntVal::uint32LE()->putValue(strlen($expected));
		$data .= $expected;
		$padded = ProtocolAsync::padRandom($data, 1024);
		$protocol->onRead($padded);
		$this->assertEquals($expected, $this->lastString);
		$this->assertEquals(FALSE, $protocol->hasWrite());
	}
	
	function testOnWriteLongMessage() {
		$sender = new ProtocolAsync($this);
		$receiver = new ProtocolAsync($this);
		$expected = serialize($_SERVER);
		$steps = ceil(strlen($expected)/$sender->getPacketLength());

		$sender->sendMessage($expected);
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$sender->onWritten();
			$receiver->onRead($data);
		}
		$this->assertEquals($expected, $this->lastString);
	}
	
	function testReceiveMessage() {
		$sender = new ProtocolAsync($this);
		$receiver = new ProtocolAsync($this);
		$expected = serialize($_SERVER);
		$steps = ceil(strlen($expected)/$sender->getPacketLength());

		$sender->sendMessage($expected);
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$sender->onWritten();
			$receiver->onRead($data);
		}
		$this->assertEquals($expected, $this->lastString);
	}
	
	function testReceiveSerialized() {
		$sender = new ProtocolAsync($this);
		$receiver = new ProtocolAsync($this);
		$sender->sendSerialize($_SERVER);
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$sender->onWritten();
			$receiver->onRead($data);
		}
		$this->assertEquals($_SERVER, $this->lastUnserialized);
	}
	
	function testReceiveStringStressTest() {
		for($i=0;$i<2048;$i++) {
			if($i==0) {
				$string = "";
			} else {
				$string = random_bytes($i);
			}
			$sender = new ProtocolAsync($this);
			$receiver = new ProtocolAsync($this);
			$sender->sendMessage($string);
			while($sender->hasWrite()) {
				$data = $sender->onWrite();
				$sender->onWritten();
				$receiver->onRead($data);
			}
			$this->assertEquals(strlen($string), strlen($this->lastString));
			$this->assertEquals($string, $this->lastString);
		}
	}
	
	function testExpectedMismatch() {
		$sender = new ProtocolAsync($this);
		$receiver = new ProtocolAsync($this);
		$receiver->expect(ProtocolAsync::MESSAGE);
		$sender->sendSerialize($_SERVER);
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$this->expectException(RuntimeException::class); 
			$receiver->onRead($data);
		}
		$this->assertEquals($_SERVER, $this->lastUnserialized);
	}
	
	function testSendOk() {
		$sender = new ProtocolAsync($this);
		$receiver = new ProtocolAsync($this);
		$receiver->expect(ProtocolAsync::OK);
		$sender->sendOK();
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$sender->onWritten();
			$receiver->onRead($data);
		}
		$this->assertEquals(TRUE, $this->lastOK);
	}

	function testSeveralMessages() {
		$sender = new ProtocolAsync($this);
		$receiver = new ProtocolAsync($this);
		$sender->sendMessage("Hello World!");
		$data = $sender->onWrite();
		$sender->onWritten();
		$receiver->onRead($data);
		$this->assertEquals("Hello World!", $this->lastString);
		
		
		$sender->sendMessage("How are you?");
		$data = $sender->onWrite();
		$sender->onWritten();
		$receiver->onRead($data);
		$this->assertEquals("How are you?", $this->lastString);
		$sender->sendOK();
		$data = $sender->onWrite();
		$sender->onWritten();
		
		$receiver->onRead($data);
		$this->assertEquals(TRUE, $this->lastOK);
	}

	/**
	 * Test that we can send several messages at once.
	 */
	function testSeveralMessagesBuffered() {
		$sender = new ProtocolAsync($this);
		$receiver = new ProtocolAsync($this);
		$sender->sendMessage("Hello World!");
		$sender->sendMessage("How are you?");
		
		$data = $sender->onWrite();
		$sender->onWritten();
		$receiver->onRead($data);
		$this->assertEquals("Hello World!", $this->lastString);
		
		$data = $sender->onWrite();
		$sender->onWritten();
		$receiver->onRead($data);
		$this->assertEquals("How are you?", $this->lastString);
		$sender->sendOK();
		$data = $sender->onWrite();
		$sender->onWritten();
		
		$receiver->onRead($data);
		$this->assertEquals(TRUE, $this->lastOK);
	}
	/*
	function testSendSmallFile() {
		$payload = random_bytes(16);
		$ss = new Net\StringSender(\Net\Protocol::FILE, $payload);

		$sender = new ProtocolAsync($this);
		$sender->sendStream($ss);
		$data = $sender->onWrite();
		$sender->onWritten();
		$this->assertEquals(chr(ProtocolAsync::FILE), substr($data, 0, 1));
		$this->assertEquals(IntVal::uint64LE()->putValue(16), substr($data, 1, 8));
		$this->assertEquals($payload, substr($data, 9, 16));
		$this->assertEquals(1024, strlen($data));
		// Nothing more to send
		$this->assertEquals(FALSE, $sender->hasWrite());
	}
	
	function testSendBlockSizedFile() {
		$payload = random_bytes(1024);
		$ss = new Net\StringSender(\Net\Protocol::FILE, $payload);
		
		$sender = new ProtocolAsync($this);
		$sender->sendStream($ss);
		$data = $sender->onWrite();
		// File Type (1 Byte)
		$this->assertEquals(chr(ProtocolAsync::FILE), substr($data, 0, 1));
		// Length as 64 bit Little Endian (8 Bytes)
		$this->assertEquals(IntVal::uint64LE()->putValue(1024), substr($data, 1, 8));
		// Payload block is 1015 bytes long
		$this->assertEquals(substr($payload, 0, 1024-9), substr($data, 9, 1024-9));
		$data = $sender->onWrite();
		$sender->onWritten();
		
		// second block is padded to 1024 bytes
		$this->assertEquals(1024, strlen($data));
		// first 9 bytes contain the remaining payload
		$this->assertEquals(substr($payload, 1024-9, 9), substr($data, 0, 9));
		// Nothing more to send.
		$this->assertEquals(FALSE, $sender->hasWrite());
	}
	
	function testSendLargerFile() {
		$payload = random_bytes(self::FILESIZE);
		$ss = new Net\StringSender(\Net\Protocol::FILE, $payload);

		$sender = new ProtocolAsync($this);
		$sender->sendStream($ss);
		$data = $sender->onWrite();
		$this->assertEquals(chr(ProtocolAsync::FILE), substr($data, 0, 1));
		$this->assertEquals(IntVal::uint64LE()->putValue(self::FILESIZE), substr($data, 1, 8));
		$this->assertEquals(substr($payload, 0, 1024-9), substr($data, 9, 1024-9));
		// We need to start with 2 since we used up one $sender->onWrite().
		$i = 2;
		while($sender->hasWrite()) {
			#if($i%10==0) {
			#	$cb = $sender->onWrite();
			#	$this->assertEquals(1, Net\Protocol::determineControlBlock($cb));
			#	$sender->onWritten();
			#	$i++;
			#continue;
			#}
			$data .= $sender->onWrite();
			$sender->onWritten();
			$i++;
		}
		// ceil(Filesize/1024)*1024
		#$this->assertEquals(2048, strlen($data));
		#$this->assertEquals(94208, strlen($data));
		// Payload is substring with offset 9 and length FILESIZE
		$this->assertEquals($payload, substr($data, 9, self::FILESIZE));
	}
	*/
	
	/*
	 * 'Stress test' which creates 'files' from 0 to 2048 bytes length. Bugs
	 * are prone to be found around the packet size equivalents.
	 */
	/*
	function testSendStressTest() {
		for($i=0;$i<2048;$i++) {
			$data = "";
			if($i==0) {
				$payload = "";
			} else {
				$payload = random_bytes($i);
			}
			
			$ss = new Net\StringSender(\Net\Protocol::FILE, $payload);

			$sender = new ProtocolAsync($this);
			$sender->sendStream($ss);
			
			
			while($sender->hasWrite()) {
				$data .= $sender->onWrite();
				$sender->onWritten();
			}
			// File Type (1 Byte)
			$this->assertEquals(chr(ProtocolAsync::FILE), substr($data, 0, 1));
			// Length as 64 bit Little Endian (8 Bytes)
			$this->assertEquals(IntVal::uint64LE()->putValue($i), substr($data, 1, 8));
			// Payload block is 1015 bytes long
			$this->assertEquals($payload, substr($data, 9, $i));
			if($i<=1015) {
				$this->assertEquals(1024, strlen($data));
			}

			if($i>1015 and $i<=2039) {
				$this->assertEquals(2048, strlen($data));
			}
	
			if($i>2039) {
				$this->assertEquals(3072, strlen($data));
			}
		}
	}
	*/
	function testReceiveSmallFile() {
		$payload = random_bytes(16);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolAsync($this);
		$sender->sendStream(new \Net\FileSender($file));
		$receiver = new ProtocolAsync($this);
		$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
		$receiver->onRead($sender->onWrite());
		$this->assertFileExists(self::getTargetName());
	}

	function testReceiveBlockSizedFile() {
		$payload = random_bytes(1024);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolAsync($this);
		$sender->sendStream(new \Net\FileSender($file));
		$receiver = new ProtocolAsync($this);
		$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
		while($sender->hasWrite()) {
			$receiver->onRead($sender->onWrite());
			$sender->onWritten();
		}
		$this->assertFileExists(self::getTargetName());
		$this->assertEquals(1024, filesize(self::getTargetName()));
		$this->assertFileEquals(self::getSourceName(), self::getTargetName());
	}
	
	/*
	 * Test at fails 2031 bytes, but it seems like the sending side is buggy. It
	 * works if ProtocolSync is sending.
	 */
	function testReceiveFileStressTest() {
		for($i=1;$i<=2048;$i++) {
			$payload = random_bytes($i);
			file_put_contents(self::getSourceName(), $payload);
			$file = new File(self::getSourceName());
			$sender = new ProtocolAsync($this);
			$sender->sendStream(new \Net\FileSender($file));
			$receiver = new ProtocolAsync($this);
			$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
			while($sender->hasWrite()) {
				$receiver->onRead($sender->onWrite());
				$sender->onWritten();
			}
			$this->assertFileExists(self::getTargetName());
			$this->assertEquals($i, filesize(self::getTargetName()));
			$this->assertFileEquals(self::getSourceName(), self::getTargetName());
			unlink(self::getSourceName());
			unlink(self::getTargetName());
		}
	}


	function testReceiveFileStressTestString() {
		for($i=1;$i<=2048;$i++) {
			$payload = random_bytes($i);
			$ss = new Net\StringSender(\Net\Protocol::FILE, $payload);
			$sr = new Net\StringReceiver();

			$sender = new ProtocolAsync($this);
			$sender->sendStream($ss);
			$receiver = new ProtocolAsync($this);
			$receiver->setFileReceiver($sr);
			while($sender->hasWrite()) {
				$receiver->onRead($sender->onWrite());
				$sender->onWritten();
			}
			#$this->assertFileExists(self::getTargetName());
			#$this->assertEquals($i, filesize(self::getTargetName()));
			$this->assertEquals($i, strlen($sr->getString()));
			#$this->assertFileEquals(self::getSourceName(), self::getTargetName());
			$this->assertEquals($payload, $sr->getString());
			#unlink(self::getSourceName());
			#unlink(self::getTargetName());
		}
	}
	
	
	function testReceiveLargerFile() {
		$payload = random_bytes(self::FILESIZE);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolAsync($this);
		$sender->sendStream(new \Net\FileSender($file));
		
		$receiver = new ProtocolAsync($this);
		$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
		while($sender->hasWrite()) {
			$receiver->onRead($sender->onWrite());
			$sender->onWritten();
		}
		$this->assertFileExists(self::getTargetName());
		$this->assertEquals(self::FILESIZE, filesize(self::getTargetName()));
		$this->assertFileEquals(self::getSourceName(), self::getTargetName());
	}

	function testReceiveMultipleFileOneByOne() {
		$payload = random_bytes(self::FILESIZE);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolAsync($this);
		$sender->sendStream(new \Net\FileSender($file));
		$receiver = new ProtocolAsync($this);
		$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
		while($sender->hasWrite()) {
			$receiver->onRead($sender->onWrite());
			$sender->onWritten();
		}
		$this->assertFileExists(self::getTargetName());
		$this->assertEquals(self::FILESIZE, filesize(self::getTargetName()));
		$this->assertFileEquals(self::getSourceName(), self::getTargetName());

		// We send a second file to test if the FileReceiver gets reused, which
		// is expected behaviour.
		$payload = random_bytes(self::FILESIZE-15);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender->sendStream(new \Net\FileSender($file));
		while($sender->hasWrite()) {
			$receiver->onRead($sender->onWrite());
			$sender->onWritten();
		}
		$this->assertFileExists(self::getTargetName());
		$this->assertEquals(self::FILESIZE-15, filesize(self::getTargetName()));
		$this->assertFileEquals(self::getSourceName(), self::getTargetName());
	}

	function testOnSentFalse() {
		$sender = new ProtocolAsync($this);
		$sender->sendMessage("do not send", $this);
		$this->assertEquals(FALSE, $this->sent);
	}

	function testOnSentTrue() {
		$sender = new ProtocolAsync($this);
		$sender->sendMessage("do not send", $this);
		$sender->onWrite();
		$sender->onWritten();
		$this->assertEquals(TRUE, $this->sent);
	}
	
	public function onCommand(ProtocolAsync $protocol, string $command) {
		$this->lastString = $command;
	}

	public function onDisconnect(ProtocolAsync $protocol) {
		
	}

	public function onMessage(ProtocolAsync $protocol, string $message) {
		$this->lastString = $message;
	}

	public function onSerialized(ProtocolAsync $protocol, $unserialized) {
		$this->lastUnserialized = $unserialized;
	}

	public function onOk(ProtocolAsync $protocol) {
		$this->lastOK = true;
	}

	public function onSent(ProtocolAsync $protocol) {
		$this->sent = TRUE;
	}

}