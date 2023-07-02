<?php
class Init {
	private $arg;
	function __construct(ArgvServe $arg) {
		$this->arg = $arg;
	}
	
	private function initPath() {
		if(file_exists(Shared::getInstancePath())) {
			throw new RuntimeException("Instance directory exists at ".Shared::getInstancePath());
		}
		$create[] = Shared::getInstancePath();
		$create[] = Shared::getSSLPath();
		$create[] = Shared::getDatabasePath();
		foreach($create as $value) {
			echo "Creating ".$value."...";
			mkdir($value, 0700);
			echo "done.".PHP_EOL;
		}
	}
	
	private function initDatabase() {
		exec("cat ".escapeshellarg(__DIR__."/../../../default-sqlite.sql")." | sqlite3 ". escapeshellarg(Shared::getDatabaseFile()));
		var_dump(file_exists(Shared::getDatabaseFile()));
	}
	
	private function initSSL() {
		$caData = array(
			"countryName" => "DE",
			"stateOrProvinceName" => "Baden-Wuerttemberg",
			"localityName" => "Stuttgart",
			"organizationName" => "ACME Inc.",
			"organizationalUnitName" => "Development",
			"commonName" => "ACME Certificate Authority",
			"emailAddress" => "floss@vm01.telton.de"
		);

		// Generate certificate
		$caKey = openssl_pkey_new();
		$caCSR = openssl_csr_new($caData, $caKey);
		$caCRT = openssl_csr_sign($caCSR, null, $caKey, 3650, array('digest_alg'=>'sha256'), rand(0, 65535));

		// Generate PEM file
		openssl_x509_export($caCRT, $caPem);
		openssl_pkey_export($caKey, $caKeyPem);
		$clientData = array(
			"countryName" => "DE",
			"stateOrProvinceName" => "Baden-Wuerttemberg",
			"localityName" => "Stuttgart",
			"organizationName" => "ACME Server Certificate",
			"organizationalUnitName" => "Development",
			"commonName" => $this->arg->getInit(),
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
		$serverCRT = openssl_csr_sign($serverCSR, $caCRT, $caKey, 3650, array('digest_alg'=>'sha256', "x509_extensions" => "v3_req"), rand(0, 65535));
		openssl_x509_export($serverCRT, $serverPem);
		openssl_pkey_export($serverKey, $serverKeyPem);
		file_put_contents(Shared::getSSLAuthorityFile(), $caPem);
		#file_put_contents(__DIR__."/ca.key", $caKeyPem);
		file_put_contents(Shared::getSSLServerCertificate(), $serverPem);
		file_put_contents(Shared::getSSLServerKey(), $serverKeyPem);
		#print_r(openssl_x509_parse($serverCRT));
	}
	
	function run() {
		$this->initPath();
		$this->initDatabase();
		$this->initSSL();
	}
}
