<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\ProtocolBase;
class ProtocolBaseTest extends TestCase {
	static function setUpBeforeClass() {
		mkdir(__DIR__."/example");
	}
	
	static function tearDownAfterClass() {
		if(file_exists(__DIR__."/example/test.bin")) {
			unlink(__DIR__."/example/test.bin");
		}
		unlink(__DIR__."/example/small.bin");
		rmdir(__DIR__."/example");
	}

	function testConstruct() {
		$socket = fopen(__DIR__."/example/test.bin", "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$this->assertInstanceOf(ProtocolBase::class, $proto);
		fclose($socket);
	}
	
	function testPadRandomShorter() {
		$padded = ProtocolBase::padRandom("Hello!", 256);
		$this->assertEquals(256, strlen($padded));
		$this->assertEquals("Hello!", substr($padded, 0, 6));
	}

	function testPadRandomEqual() {
		$padded = ProtocolBase::padRandom("Equality", 8);
		$this->assertEquals(8, strlen($padded));
		$this->assertEquals("Equality", $padded);
	}

	function testPadRandomLonger() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("padlength 8 shorter than strlen 24");
		$padded = ProtocolBase::padRandom("This string is too long.", 8);
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
		$this->assertEquals(32, ftell($socket));
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
	/**
	 * Some errors appeared in specific conditions, ie lengths. Here, we fill an
	 * array with strings from one byte to the whole message, looking for errors.
	 */
	function testDifferentSizes() {
		$msg = "This is a good deal of words which say little of value, but are more than 16 or 32 bytes.";
		$array = array();
		for($i=0;$i<strlen($msg); $i++) {
			$array[] = substr($msg, 0, $i+1);
		}
		$filename = __DIR__."/example/test.bin";
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket, 16, 16);
		foreach($array as $value) {
			$proto->sendString(ProtocolBase::MESSAGE, $value);
		}
		fclose($socket);

		$read = fopen($filename, "r");
		$reader = new ProtocolBase($read, 16, 16);

		$compare = array();
		for($i=0;$i<strlen($msg); $i++) {
			$compare[] = $reader->getString(ProtocolBase::MESSAGE);
		}
		fclose($read);
		$this->assertEquals($array, $compare);
		#$proto->sendString(ProtocolBase::MESSAGE, "AB");
	}
	
	function testSendSerialize() {
		$filename = __DIR__."/example/test.bin";
		$file = new File($filename);
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket, 16, 16);
		$proto->sendSerializePHP($file);
		fclose($socket);
		
		$socket = fopen($filename, "r");
		$proto = new ProtocolBase($socket, 16, 16);
		$unserialized = $proto->getSerializedPHP($file);
		fclose($socket);
		$this->assertInstanceOf(File::class, $unserialized);
	}
	
	function testSendVerySmallFile() {
		$sampleString = "Hello World.";
		file_put_contents(__DIR__."/example/small.bin", $sampleString);
		$file = new File(__DIR__."/example/small.bin");
		
		$sender = new \Net\FileSender($file);
		$filename = __DIR__."/example/test.bin";
		$socket = fopen($filename, "w");
		$proto = new ProtocolBase($socket);
		$proto->sendStream($sender);
		fclose($socket);
		
		$padded = file_get_contents(__DIR__."/example/test.bin");
		$this->assertEquals(chr(ProtocolBase::FILE).chr(12)."\0\0\0\0\0\0\0", substr($padded, 0, 9));
		$this->assertEquals("Hello World.", substr($padded, 9, 12));
		$this->assertEquals(1024, strlen($padded));
	}
}
