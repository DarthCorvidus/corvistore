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
	}
	
	static function getSourceName() {
		return __DIR__."/example.bin";
	}
	
	
	function testConstruct() {
		$protocol = new ProtocolReactive($this);
		$this->assertInstanceOf(ProtocolReactive::class, $protocol);
	}
	
	function testGetDefaultSize() {
		$protocol = new ProtocolReactive($this);
		$this->assertEquals(1024, $protocol->getPacketLength("x", 0));
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
		$write = $protocol->onWrite("x", 0);
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
		$steps = ceil(strlen($expected)/$sender->getPacketLength(" ", 0));

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
		$steps = ceil(strlen($expected)/$sender->getPacketLength(" ", 0));

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
		$data = $sender->onWrite("x", 0);
		$receiver->onRead($data);
		$this->assertEquals("How are you?", $this->lastString);
		$sender->sendOK();
		$data = $sender->onWrite();
		$receiver->onRead($data);
		$this->assertEquals(TRUE, $this->lastOK);
	}
	
	#function testSendSmallFile() {
	#	$payload = random_bytes(16);
	#	file_put_contents(self::getSourceName(), $payload);
	#	$file = new File(self::getSourceName());
	#	$sender = new ProtocolReactive($this);
	#	$sender->sendFile(new \Net\FileSender($file));
	#	$data = $sender->onWrite("x", 0);
	#	$this->assertEquals();
	#	
	#	
	#}

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