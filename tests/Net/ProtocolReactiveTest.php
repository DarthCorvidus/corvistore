<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\ProtocolReactive;
class ProtocolReactiveTest extends TestCase implements Net\ProtocolReactiveListener {
	private $lastString;
	private $lastUnserialized;
	private $lastOK = TRUE;
	const FILESIZE = 93821;
	function __construct() {
		parent::__construct();
	}
	function setUp() {
		$this->lastString = NULL;
		$this->lastUnserialized = array();
		$this->lastOK = FALSE;
	}
	
	function tearDown() {
		$this->lastString = NULL;
		$this->lastUnserialized;
		$this->lastOK = FALSE;
		if(file_exists(self::getSourceName())) {
			unlink(self::getSourceName());
		}
		if(file_exists(self::getTargetName())) {
			unlink(self::getTargetName());
		}
	}
	
	static function getSourceName() {
		return __DIR__."/source.bin";
	}

	static function getTargetName() {
		return __DIR__."/target.bin";
	}

	function testConstruct() {
		$protocol = new ProtocolReactive($this);
		$this->assertInstanceOf(ProtocolReactive::class, $protocol);
	}
	
	function testGetDefaultSize() {
		$protocol = new ProtocolReactive($this);
		$this->assertEquals(1024, $protocol->getPacketLength());
	}
	
	#function testGetStackSize() {
	#	$protocol = new ProtocolReactive($this);
	#	$string = serialize($_SERVER);
	#	$steps = (int)ceil(strlen($string)/$protocol->getPacketLength(" ", 0));
	#	$protocol->sendMessage($string);
	#	$this->assertEquals($steps, $protocol->getStackSize());
	#}
	
	function testOnWriteShortCommand() {
		$expected = chr(ProtocolReactive::COMMAND).IntVal::uint32LE()->putValue(4)."quit";
		$protocol = new ProtocolReactive($this);
		$protocol->sendCommand("quit");
		$write = $protocol->onWrite();
		$this->assertEquals(1024, strlen($write));
		$this->assertEquals($expected, substr($write, 0, 1+4+4));
	}
	
	function testOnReadShortCommand() {
		$protocol = new ProtocolReactive($this);
		$expected = "quit";
		$data = chr(ProtocolReactive::COMMAND);
		$data .= IntVal::uint32LE()->putValue(strlen($expected));
		$data .= $expected;
		$padded = ProtocolReactive::padRandom($data, 1024);
		$protocol->onRead($padded);
		$this->assertEquals($expected, $this->lastString);
		$this->assertEquals(FALSE, $protocol->hasWrite());
	}
	
	function testOnWriteLongMessage() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$expected = serialize($_SERVER);
		$steps = ceil(strlen($expected)/$sender->getPacketLength());

		$sender->sendMessage($expected);
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$receiver->onRead($data);
		}
		$this->assertEquals($expected, $this->lastString);
	}
	
	function testReceiveMessage() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$expected = serialize($_SERVER);
		$steps = ceil(strlen($expected)/$sender->getPacketLength());

		$sender->sendMessage($expected);
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$receiver->onRead($data);
		}
		$this->assertEquals($expected, $this->lastString);
	}
	
	function testReceiveSerialized() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$sender->sendSerialize($_SERVER);
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$receiver->onRead($data);
		}
		$this->assertEquals($_SERVER, $this->lastUnserialized);
	}
	
	function testExpectedMismatch() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$receiver->expect(ProtocolReactive::MESSAGE);
		$sender->sendSerialize($_SERVER);
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$this->expectException(RuntimeException::class); 
			$receiver->onRead($data);
		}
		$this->assertEquals($_SERVER, $this->lastUnserialized);
	}
	
	function testSendOk() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$receiver->expect(ProtocolReactive::OK);
		$sender->sendOK();
		while($sender->hasWrite()) {
			$data = $sender->onWrite();
			$receiver->onRead($data);
		}
		$this->assertEquals(TRUE, $this->lastOK);
	}

	function testSeveralMessages() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$sender->sendMessage("Hello World!");
		$data = $sender->onWrite();
		$receiver->onRead($data);
		$this->assertEquals("Hello World!", $this->lastString);
		$sender->sendMessage("How are you?");
		$data = $sender->onWrite();
		$receiver->onRead($data);
		$this->assertEquals("How are you?", $this->lastString);
		$sender->sendOK();
		$data = $sender->onWrite();
		$receiver->onRead($data);
		$this->assertEquals(TRUE, $this->lastOK);
	}
	
	function testSendSmallFile() {
		$payload = random_bytes(16);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolReactive($this);
		$sender->sendFile(new \Net\FileSender($file));
		$data = $sender->onWrite();
		$this->assertEquals(chr(ProtocolReactive::FILE), substr($data, 0, 1));
		$this->assertEquals(IntVal::uint64LE()->putValue(16), substr($data, 1, 8));
		$this->assertEquals($payload, substr($data, 9, 16));
		$this->assertEquals(1024, strlen($data));
		// Nothing more to send
		$this->assertEquals(FALSE, $sender->hasWrite());
	}
	
	function testSendBlockSizedFile() {
		$payload = random_bytes(1024);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolReactive($this);
		$sender->sendFile(new \Net\FileSender($file));
		$data = $sender->onWrite();
		// File Type (1 Byte)
		$this->assertEquals(chr(ProtocolReactive::FILE), substr($data, 0, 1));
		// Length as 64 bit Little Endian (8 Bytes)
		$this->assertEquals(IntVal::uint64LE()->putValue(1024), substr($data, 1, 8));
		// Payload block is 1015 bytes long
		$this->assertEquals(substr($payload, 0, 1024-9), substr($data, 9, 1024-9));
		$data = $sender->onWrite();
		
		// second block is padded to 1024 bytes
		$this->assertEquals(1024, strlen($data));
		// first 9 bytes contain the remaining payload
		$this->assertEquals(substr($payload, 1024-9, 9), substr($data, 0, 9));
		// Nothing more to send.
		$this->assertEquals(FALSE, $sender->hasWrite());
	}
	
	function testSendLargerFile() {
		$payload = random_bytes(self::FILESIZE);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolReactive($this);
		$sender->sendFile(new \Net\FileSender($file));
		$data = $sender->onWrite();
		$this->assertEquals(chr(ProtocolReactive::FILE), substr($data, 0, 1));
		$this->assertEquals(IntVal::uint64LE()->putValue(self::FILESIZE), substr($data, 1, 8));
		$this->assertEquals(substr($payload, 0, 1024-9), substr($data, 9, 1024-9));
		while($sender->hasWrite()) {
			$data .= $sender->onWrite();
		}
		// ceil(Filesize/1024)*1024
		$this->assertEquals(94208, strlen($data));
		// Payload is substring with offset 9 and length FILESIZE
		$this->assertEquals($payload, substr($data, 9, self::FILESIZE));
	}
	
	function testReceiveSmallFile() {
		$payload = random_bytes(16);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolReactive($this);
		$sender->sendFile(new \Net\FileSender($file));
		$receiver = new ProtocolReactive($this);
		$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
		$receiver->onRead($sender->onWrite());
		$this->assertFileExists(self::getTargetName());
	}

	function testReceiveBlockSizedFile() {
		$payload = random_bytes(1024);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolReactive($this);
		$sender->sendFile(new \Net\FileSender($file));
		$receiver = new ProtocolReactive($this);
		$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
		$receiver->onRead($sender->onWrite());
		$receiver->onRead($sender->onWrite());
		$this->assertFileExists(self::getTargetName());
		$this->assertEquals(1024, filesize(self::getTargetName()));
		$this->assertFileEquals(self::getSourceName(), self::getTargetName());
	}
	
	function testReceiveLargerFile() {
		$payload = random_bytes(self::FILESIZE);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolReactive($this);
		$sender->sendFile(new \Net\FileSender($file));
		
		$receiver = new ProtocolReactive($this);
		$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
		while($sender->hasWrite()) {
			$receiver->onRead($sender->onWrite());
		}
		$this->assertFileExists(self::getTargetName());
		$this->assertEquals(self::FILESIZE, filesize(self::getTargetName()));
		$this->assertFileEquals(self::getSourceName(), self::getTargetName());
	}

	function testReceiveMultipleFileOneByOne() {
		$payload = random_bytes(self::FILESIZE);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender = new ProtocolReactive($this);
		$sender->sendFile(new \Net\FileSender($file));
		$receiver = new ProtocolReactive($this);
		$receiver->setFileReceiver(new Net\FileReceiver(self::getTargetName()));
		while($sender->hasWrite()) {
			$receiver->onRead($sender->onWrite());
		}
		$this->assertFileExists(self::getTargetName());
		$this->assertEquals(self::FILESIZE, filesize(self::getTargetName()));
		$this->assertFileEquals(self::getSourceName(), self::getTargetName());

		// We send a second file to test if the FileReceiver gets reused, which
		// is expected behaviour.
		$payload = random_bytes(self::FILESIZE-15);
		file_put_contents(self::getSourceName(), $payload);
		$file = new File(self::getSourceName());
		$sender->sendFile(new \Net\FileSender($file));
		while($sender->hasWrite()) {
			$receiver->onRead($sender->onWrite());
		}
		$this->assertFileExists(self::getTargetName());
		$this->assertEquals(self::FILESIZE-15, filesize(self::getTargetName()));
		$this->assertFileEquals(self::getSourceName(), self::getTargetName());
	}
	
	public function onCommand(ProtocolReactive $protocol, string $command) {
		$this->lastString = $command;
	}

	public function onDisconnect(ProtocolReactive $protocol) {
		
	}

	public function onMessage(ProtocolReactive $protocol, string $message) {
		$this->lastString = $message;
	}

	public function onSerialized(ProtocolReactive $protocol, $unserialized) {
		$this->lastUnserialized = $unserialized;
	}

	public function onOk(ProtocolReactive $protocol) {
		$this->lastOK = true;
	}

}