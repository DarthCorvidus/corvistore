<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\ProtocolReactive;
class ProtocolReactiveTest extends TestCase implements Net\ProtocolReactiveListener {
	private $lastString;
	private $lastUnserialized;
	function __construct() {
		parent::__construct();
	}
	function setUp() {
		$this->lastString = NULL;
		$this->lastUnserialized = array();
	}
	
	function tearDown() {
		$this->lastString = NULL;
		$this->lastUnserialized;
	}
	
	
	function testConstruct() {
		$protocol = new ProtocolReactive($this);
		$this->assertInstanceOf(ProtocolReactive::class, $protocol);
	}
	
	function testGetStackSize() {
		$protocol = new ProtocolReactive($this);
		$string = serialize($_SERVER);
		$steps = (int)ceil(strlen($string)/$protocol->getPacketLength(" ", 0));
		$protocol->sendMessage($string);
		$this->assertEquals($steps, $protocol->getStackSize());
	}
	
	function testReceiveShortCommand() {
		$protocol = new ProtocolReactive($this);
		$expected = "quit";
		$data = chr(ProtocolReactive::COMMAND);
		$data .= IntVal::uint32LE()->putValue(strlen($expected));
		$data .= $expected;
		$padded = ProtocolReactive::padRandom($data, 1024);
		$protocol->onRead("squid", 0, $padded);
		$this->assertEquals($expected, $this->lastString);
	}
	
	function testReceiveLongMessage() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$expected = serialize($_SERVER);
		$steps = ceil(strlen($expected)/$sender->getPacketLength(" ", 0));

		$sender->sendMessage($expected);
		while($sender->hasWrite("x", 0)) {
			$data = $sender->onWrite("x", 0);
			$receiver->onRead("x", 0, $data);
		}
		$this->assertEquals($expected, $this->lastString);
	}

	function testReceiveMessage() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$expected = serialize($_SERVER);
		$steps = ceil(strlen($expected)/$sender->getPacketLength(" ", 0));

		$sender->sendMessage($expected);
		while($sender->hasWrite("x", 0)) {
			$data = $sender->onWrite("x", 0);
			$receiver->onRead("x", 0, $data);
		}
		$this->assertEquals($expected, $this->lastString);
	}

	function testReceiveSerialized() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$sender->sendSerialize($_SERVER);
		while($sender->hasWrite("x", 0)) {
			$data = $sender->onWrite("x", 0);
			$receiver->onRead("x", 0, $data);
		}
		$this->assertEquals($_SERVER, $this->lastUnserialized);
	}
	
	function testExpectedMismatch() {
		$sender = new ProtocolReactive($this);
		$receiver = new ProtocolReactive($this);
		$receiver->expect(ProtocolReactive::MESSAGE);
		$sender->sendSerialize($_SERVER);
		while($sender->hasWrite("x", 0)) {
			$data = $sender->onWrite("x", 0);
			$this->expectException(RuntimeException::class); 
			$receiver->onRead("x", 0, $data);
		}
		$this->assertEquals($_SERVER, $this->lastUnserialized);
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

}