<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class ProtocolSyncTest extends TestCase {
	const FILE_SIZE = 17201;
	function testConstruct() {
		$stream = new StreamFake("");
		$protocol = new \Net\ProtocolSync($stream);
		$this->assertInstanceOf(\Net\ProtocolSync::class, $protocol);
	}
	
	function tearDown() {
		if(file_exists(self::getExamplePath())) {
			unlink(self::getExamplePath());
		}
	}
	
	static function getExamplePath(): string {
		return __DIR__."/example.bin";
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
	
	/*
	function testSendStreamSmall() {
		file_put_contents(self::getExamplePath(), "Hello World!");
		$file = File::fromPath(self::getExamplePath());
		
		$fileStream = new \Net\FileSender($file);
		$fakeStream = new StreamFake("");
		
		
		$proto = new Net\ProtocolSync($fakeStream);
		$proto->sendStream($fileStream);
		$read = $fakeStream->read(1024);
		$this->assertEquals(\Net\Protocol::FILE, ord($read[0]));
		$this->assertEquals(12, \IntVal::uint64LE()->getValue(substr($read, 1, 8)));
		$this->assertEquals("Hello World!", substr($read, 9, 12));
		$this->assertEquals(1024, strlen($fakeStream->getData()));
	}
	*/
	/*
	 * File which fits into exactly one block along with the header, ie the
	 * payload is 1015 bytes long.
	 */
	/*
	function testSendBlockMinusHeader() {
		$expected = random_bytes(1024-9);
		file_put_contents(self::getExamplePath(), $expected);
		$file = File::fromPath(self::getExamplePath());

		$fileStream = new \Net\FileSender($file);
		$fakeStream = new StreamFake("");
		
		$proto = new Net\ProtocolSync($fakeStream);
		$proto->sendStream($fileStream);
		$data = $fakeStream->getData();
		
		$this->assertEquals(1024, strlen($data));
		$this->assertEquals(\Net\Protocol::FILE, ord($data[0]));
		$this->assertEquals(1015, \IntVal::uint64LE()->getValue(substr($data, 1, 8)));
		$this->assertEquals($expected, substr($data, 9, 1015));
	}
	*/
	
	/*
	 * Test file which is exactly the size of one block; as the header adds 9
	 * bytes, 2048 bytes need to be transferred.
	 */
	/*
	function testSendBlockSized() {
		$expected = random_bytes(1024);
		file_put_contents(self::getExamplePath(), $expected);
		$file = File::fromPath(self::getExamplePath());

		$fileStream = new \Net\FileSender($file);
		$fakeStream = new StreamFake("");
		
		$proto = new Net\ProtocolSync($fakeStream);
		$proto->sendStream($fileStream);
		$data = $fakeStream->getData();
		
		$this->assertEquals(2048, strlen($data));
		$this->assertEquals(\Net\Protocol::FILE, ord($data[0]));
		$this->assertEquals(1024, \IntVal::uint64LE()->getValue(substr($data, 1, 8)));
		$this->assertEquals($expected, substr($data, 9, 1024));
	}
	*/
	/*
	function testSendLarge() {
		$expected = random_bytes(self::FILE_SIZE);
		file_put_contents(self::getExamplePath(), $expected);
		$file = File::fromPath(self::getExamplePath());

		$padLen = (int)(ceil(self::FILE_SIZE/1024)*1024);
		
		$fileStream = new \Net\FileSender($file);
		$fakeStream = new StreamFake("");
		
		$proto = new Net\ProtocolSync($fakeStream);
		$proto->sendStream($fileStream);
		$data = $fakeStream->getData();
		
		$this->assertEquals($padLen, strlen($data));
		$this->assertEquals(\Net\Protocol::FILE, ord($data[0]));
		$this->assertEquals(self::FILE_SIZE, \IntVal::uint64LE()->getValue(substr($data, 1, 8)));
		$this->assertEquals($expected, substr($data, 9, self::FILE_SIZE));
	}
	*/
	
	function testGetCommand() {
		$payload = "HELLO WORLD";
		$data = chr(\Net\ProtocolSync::COMMAND);
		$data .= \IntVal::uint32LE()->putValue(strlen($payload));
		$data .= $payload;
		
		$fs = new StreamFake(\Net\Protocol::padRandom($data, 1024));
		
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
		
		$fs = new StreamFake(\Net\Protocol::padRandom($header.$serializedCats, 1024*22));
		
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
		
		$fs = new StreamFake(\Net\Protocol::padRandom($header.$serializedCats, 1024*22));
		
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
		
		$fs = new StreamFake(\Net\Protocol::padRandom($header.$serializedCats, 1024*22));
		
		$protocol = new \Net\ProtocolSync($fs);
		$this->expectException(\Net\ProtocolMismatchException::class);
		$protocol->getMessage();
	}
	
	/*
	function testGetFile() {
		$payload = "Hello world!";
		$header = chr(\Net\Protocol::FILE);
		$header .= \IntVal::uint64LE()->putValue(strlen($payload));
		
		$fs = new StreamFake(\Net\Protocol::padRandom($header.$payload, 1024));
		$sr = new \Net\StringReceiver();
		
		$protocol = new \Net\ProtocolSync($fs);
		$protocol->getStream($sr);
		$this->assertEquals($payload, $sr->getString());
	}
	*/
	function testGetFileStressTest() {
		for($i=0;$i<=2048;$i++) {
			if($i==0) {
				$payload = "";
			} else {
				$payload = random_bytes($i);
			}
			
			$stream = new StreamFake("");
			
			$ss = new \Net\StringSender(\Net\Protocol::FILE, $payload);
			$sr = new \Net\StringReceiver();
			
			
			
			
			
			$recProto = new \Net\ProtocolSync($stream);
			$sendProto = new \Net\ProtocolSync($stream);
			$sendProto->sendStream($ss);
			$recProto->getStream($sr);
			
			$this->assertEquals($payload, $sr->getString());
			
		}
	}
	
	function testSendGetOk() {
		$sf = new \StreamFake("");
		$send = new \Net\ProtocolSync($sf);
		$receive = new \Net\ProtocolSync($sf);
		$send->sendOK();
		$receive->getOK();
		$this->assertEquals(TRUE, $sf->eof());
	}
	
	function testSendGetMessage() {
		$expected = "The cat is on the mat.";
		$sf = new \StreamFake("");
		$send = new \Net\ProtocolSync($sf);
		$receive = new \Net\ProtocolSync($sf);
		$send->sendMessage($expected);
		$message = $receive->getMessage();
		$this->assertEquals($expected, $message);
		$this->assertEquals(TRUE, $sf->eof());
	}

	function testSendGetCommand() {
		$expected = "QUIT";
		$sf = new \StreamFake("");
		$send = new \Net\ProtocolSync($sf);
		$receive = new \Net\ProtocolSync($sf);
		$send->sendCommand($expected);
		$message = $receive->getCommand();
		$this->assertEquals($expected, $message);
		$this->assertEquals(TRUE, $sf->eof());
	}

	function testSendGetSerialized() {
		$expected = $_SERVER;
		$sf = new \StreamFake("");
		$send = new \Net\ProtocolSync($sf);
		$receive = new \Net\ProtocolSync($sf);
		$send->sendSerialize($expected);
		$message = $receive->getSerialized();
		$this->assertEquals($expected, $message);
		$this->assertEquals(TRUE, $sf->eof());
	}

	function testSendGetStream() {
		#$expected = random_bytes(self::FILE_SIZE);
		$expected = "The cat is on the mat.";
		$sf = new \StreamFake("");
		
		$send = new \Net\ProtocolSync($sf);
		$receive = new \Net\ProtocolSync($sf);
		
		$send->sendStream(new \Net\StringSender(\Net\Protocol::FILE, $expected));
		
		$mr = new \Net\MockReceiver();
		$receive->getStream($mr);
		
		$this->assertEquals($expected, $mr->getString());
		$this->assertEquals(TRUE, $sf->eof());
		$this->assertEquals(TRUE, $mr->hasStarted());
		$this->assertEquals(TRUE, $mr->hasEnded());
		$this->assertEquals(FALSE, $mr->wasCancelled());
		
	}
	
}
