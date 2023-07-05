<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\ProtocolBase;
class ProtocolBaseTest extends TestCase {
	function testConstruct() {
		$socket = fopen(__DIR__."/example/test.bin", "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$this->assertInstanceOf(ProtocolBase::class, $proto);
		fclose($socket);
	}
	
	function testSendString() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen(__DIR__."/example/test.bin", "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::MESSAGE, "Hello.");
		$this->assertEquals(16, ftell($socket));
		fclose($socket);
	}
	
	function testSendStringTwo() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::MESSAGE, "This is less than 32.");
		$this->assertEquals(32, ftell($socket));
		fclose($socket);
	}
	
	function testSendStringMore() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::MESSAGE, "This is a good deal of words which say little of value, but are more than 16 or 32 bytes.");
		$this->assertEquals(96, ftell($socket));
		fclose($socket);
	}
	
	function testReceiveString() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen(__DIR__."/example/test.bin", "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::MESSAGE, "Hello.");
		fclose($socket);
		
		$read = fopen($filename, "r");
		$reader = new ProtocolBase($read, 16, 16);
		$this->assertEquals("Hello.", $reader->getString(ProtocolBase::MESSAGE));
	}

	function testReceiveTwo() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen(__DIR__."/example/test.bin", "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::MESSAGE, "This is less than 32.");
		fclose($socket);
		
		$read = fopen($filename, "r");
		$reader = new ProtocolBase($read, 16, 16);
		$this->assertEquals("This is less than 32.", $reader->getString(ProtocolBase::MESSAGE));
		fclose($read);
	}

	function testReceiveStringMore() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::MESSAGE, "This is a good deal of words which say little of value, but are more than 16 or 32 bytes.");
		$this->assertEquals(96, ftell($socket));
		fclose($socket);
		
		$read = fopen($filename, "r");
		$reader = new ProtocolBase($read, 16, 16);
		$this->assertEquals("This is a good deal of words which say little of value, but are more than 16 or 32 bytes.", $reader->getString(ProtocolBase::MESSAGE));
		fclose($read);
	}
	
	function testSendTypeMismatch() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::COMMAND, "This is a good deal of words which say little of value, but are more than 16 or 32 bytes.");
		$this->assertEquals(96, ftell($socket));
		fclose($socket);
		
		$read = fopen($filename, "r");
		$reader = new ProtocolBase($read, 16, 16);
		$this->expectException(Net\ProtocolMismatchException::class);
		$this->expectExceptionMessage("Protocol mismatch: expected ". \Net\ProtocolBase::MESSAGE.", got ". \Net\ProtocolBase::COMMAND);
		$reader->getString(ProtocolBase::MESSAGE);
		fclose($read);
	}

	function testReceiveError() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::ERROR, "Out of memory");
		$this->assertEquals(16, ftell($socket));
		fclose($socket);
		
		$read = fopen($filename, "r");
		$reader = new ProtocolBase($read, 16, 16);
		$this->expectException(Net\ProtocolErrorException::class);
		$this->expectExceptionMessage("Out of memory");
		$reader->getString(ProtocolBase::FILE);
		fclose($read);
	}
	
	function testReceiveLongError() {
		$filename = __DIR__."/example/test.bin";
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendString(ProtocolBase::ERROR, "Well, you should not have done that. Really not. Now I am not only out of memory, but sad too.");
		$this->assertEquals(112, ftell($socket));
		fclose($socket);
		
		$read = fopen($filename, "r");
		$reader = new ProtocolBase($read, 16, 16);
		$this->expectException(Net\ProtocolErrorException::class);
		$this->expectExceptionMessage("Well, you should not have done that. Really not. Now I am not only out of memory, but sad too.");
		$reader->getString(ProtocolBase::FILE);
		fclose($read);
	}


}
