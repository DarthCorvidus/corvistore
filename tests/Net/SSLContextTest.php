<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Net\SSLContext;
class SSLContextTest extends TestCase {
	function testConstruct() {
		$context = new SSLContext();
		$this->assertInstanceOf(SSLContext::class, $context);
	}
	
	static function setUpBeforeClass() {
		self::generateSSL("01");
		self::generateSSL("02");
	}

	static function tearDownAfterClass() {
		unlink(__DIR__."/ca.01.crt");
		unlink(__DIR__."/ca.01.key");
		unlink(__DIR__."/server.01.key");
		unlink(__DIR__."/server.01.crt");
		
		unlink(__DIR__."/ca.02.crt");
		unlink(__DIR__."/ca.02.key");
		unlink(__DIR__."/server.02.key");
		unlink(__DIR__."/server.02.crt");
	}
	
	
	static function generateSSL($id) {
		$caData = array(
			"countryName" => "DE",
			"stateOrProvinceName" => "Baden-Wuerttemberg",
			"localityName" => "Stuttgart",
			"organizationName" => "Crow Protect Ltd.",
			"organizationalUnitName" => "Development",
			"commonName" => "Crow Protect Certificate Authority",
			"emailAddress" => "floss@vm01.telton.de"
		);

		// Generate certificate
		$caKey = openssl_pkey_new();
		$caCSR = openssl_csr_new($caData, $caKey);
		$caCRT = openssl_csr_sign($caCSR, null, $caKey, 365, array('digest_alg'=>'sha256'), rand(0, 65535));

		// Generate PEM file
		openssl_x509_export($caCRT, $caPem);
		openssl_pkey_export($caKey, $caKeyPem);
		#file_put_contents(__DIR__."/ca.crt", $caPem);
		#file_put_contents(__DIR__."/ca.key", $caKeyPem);
		// Generate Client
		$clientData = array(
			"countryName" => "DE",
			"stateOrProvinceName" => "Baden-Wuerttemberg",
			"localityName" => "Stuttgart",
			"organizationName" => "Crow Protect Ltd.",
			"organizationalUnitName" => "Development",
			"commonName" => "backup.example.com",
			"emailAddress" => "floss@vm01.telton.de"
		);
		$serverKey = openssl_pkey_new();
		#$SSLcnf = array(
		#	'config' => '/usr/local/nessy2/share/ssl/openssl.cnf',
	    #    'x509_extensions' => 'v3_ca',
        #);
		
		#$extra["basicConstraints"] = "CA:FALSE";
		#$options["config_section_section"] = " v3_req ";
		$serverCSR = openssl_csr_new($clientData, $serverKey);
		$serverCRT = openssl_csr_sign($serverCSR, $caCRT, $caKey, 365, array('digest_alg'=>'sha256', "x509_extensions" => "v3_req"), rand(0, 65535));
		openssl_x509_export($serverCRT, $serverPem);
		openssl_pkey_export($serverKey, $serverKeyPem);
		file_put_contents(__DIR__."/ca.".$id.".crt", $caPem);
		file_put_contents(__DIR__."/ca.".$id.".key", $caKeyPem);
		file_put_contents(__DIR__."/server.".$id.".crt", $serverPem);
		file_put_contents(__DIR__."/server.".$id.".key", $serverKeyPem);
		#print_r(openssl_x509_parse($serverCRT));
	}
	
	function testSetCA() {
		$context = new SSLContext();
		$this->assertEquals(NULL, $context->setCAFile(__DIR__."/ca.01.crt"));
	}
	
	function testSetBogusCA() {
		$filename = __DIR__."/ca-bogus.crt";
		$context = new SSLContext();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Root certificate ".$filename." not found.");
		$context->setCAFile($filename);
	}

	function testSetInvalidCA() {
		$filename = __DIR__."/pseudo-ca.crt";
		$context = new SSLContext();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Root certificate ".$filename." is not a valid PEM file.");
		$context->setCAFile($filename);
	}

	function testSetCertificateFile() {
		$context = new SSLContext();
		$this->assertEquals(NULL, $context->setCertificateFile(__DIR__."/server.01.crt"));
	}
	
	function testSetBogusCF() {
		$filename = __DIR__."/server-bogus.crt";
		$context = new SSLContext();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Server certificate ".$filename." not found.");
		$context->setCertificateFile($filename);
	}

	function testSetInvalidCF() {
		$filename = __DIR__."/pseudo.crt";
		$context = new SSLContext();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Server certificate ".$filename." is not a valid PEM file.");
		$context->setCertificateFile($filename);
	}

	function testSetKeyFile() {
		$context = new SSLContext();
		$this->assertEquals(NULL, $context->setPrivateKeyFile(__DIR__."/server.01.key"));
	}
	
	function testSetBogusKF() {
		$filename = __DIR__."/server-bogus.key";
		$context = new SSLContext();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Server key ".$filename." not found.");
		$context->setPrivateKeyFile($filename);
	}

	function testSetInvalidKF() {
		$filename = __DIR__."/pseudo.key";
		$context = new SSLContext();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Server key ".$filename." is not a valid PEM file.");
		$context->setPrivateKeyFile($filename);
	}

}
