<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class ProtocolSyncTest extends TestCase {
	function testConstruct() {
		$stream = new StreamFake("");
		$protocol = new \Net\ProtocolSync($stream);
		$this->assertInstanceOf(\Net\ProtocolSync::class, $protocol);
	}
	
	function testSendCommand() {
		$stream = new StreamFake("");
		$protocol = new \Net\ProtocolSync($stream);
		$protocol->sendCommand("HELLO WORLD");
		$data = $stream->getData();
		
		$this->assertEquals(\Net\ProtocolSync::COMMAND, ord($data[0]));
		$this->assertEquals(11, \IntVal::uint32LE()->getValue(substr($data, 1, 4)));
		$this->assertEquals(1024, strlen($data));
		$this->assertEquals("HELLO WORLD", substr($data, 5, 11));
	}
	
	function testSendLongMessage() {
		$array = array();
		for($i=0;$i<1024;$i++) {
			$array[] = "Cat # ".$i;
		}
		$serializedCats = serialize($array);
		$len = strlen($serializedCats);

		$stream = new StreamFake("");
		$protocol = new \Net\ProtocolSync($stream);
		$protocol->sendMessage($serializedCats);
		$data = $stream->getData();
		
		$this->assertEquals(\Net\ProtocolSync::MESSAGE, ord($data[0]));
		$this->assertEquals($len, \IntVal::uint32LE()->getValue(substr($data, 1, 4)));
		$this->assertEquals($serializedCats, substr($data, 5, $len));
		$this->assertEquals(22*1024, strlen($data));
	}
	
	function testGetCommand() {
		$payload = "HELLO WORLD";
		$data = chr(\Net\ProtocolSync::COMMAND);
		$data .= \IntVal::uint32LE()->putValue(strlen($payload));
		$data .= $payload;
		
		$fs = new StreamFake(\Net\ProtocolReactive::padRandom($data, 1024));
		
		$protocol = new \Net\ProtocolSync($fs);
		$command = $protocol->getCommand();
		$this->assertEquals($payload, $command);
	}
	
	function testGetMessage() {
		$array = array();
		for($i=0;$i<1024;$i++) {
			$array[] = "Cat # ".$i;
		}
		$serializedCats = serialize($array);
		$len = strlen($serializedCats);
		$header = chr(\Net\ProtocolSync::MESSAGE).\IntVal::uint32LE()->putValue($len);
		
		$fs = new StreamFake(\Net\ProtocolReactive::padRandom($header.$serializedCats, 1024*22));
		
		$protocol = new \Net\ProtocolSync($fs);
		$this->assertEquals($serializedCats, $protocol->getMessage());
	}
	
	function testGetSerialized() {
		$array = array();
		for($i=0;$i<1024;$i++) {
			$array[] = "Cat # ".$i;
		}
		$serializedCats = serialize($array);
		$len = strlen($serializedCats);
		$header = chr(\Net\ProtocolSync::SERIALIZED_PHP).\IntVal::uint32LE()->putValue($len);
		
		$fs = new StreamFake(\Net\ProtocolReactive::padRandom($header.$serializedCats, 1024*22));
		
		$protocol = new \Net\ProtocolSync($fs);
		$this->assertEquals($array, $protocol->getSerialized());
	}
	
	function testProtocolMismatch() {
		$array = array();
		for($i=0;$i<1024;$i++) {
			$array[] = "Cat # ".$i;
		}
		$serializedCats = serialize($array);
		$len = strlen($serializedCats);
		$header = chr(\Net\ProtocolSync::SERIALIZED_PHP).\IntVal::uint32LE()->putValue($len);
		
		$fs = new StreamFake(\Net\ProtocolReactive::padRandom($header.$serializedCats, 1024*22));
		
		$protocol = new \Net\ProtocolSync($fs);
		$this->expectException(\Net\ProtocolMismatchException::class);
		$protocol->getMessage();
	}

}
