<?php
namespace Net;
/**
 * Description of SSLContext
 * 
 * In no way I'm going to rely on stream_context_create() alone, which does
 * about zero, like in 0, error handling.
 *
 * @author hm
 */
class SSLContext {
	private string $ca;
	private string $cert;
	private string $key;
	function __construct() {
	}
	
	public function setCAFile(string $filename): void {
		if(!file_exists($filename)) {
			throw new \RuntimeException("Root certificate ".$filename." not found.");
		}
		$crt = @openssl_x509_read(file_get_contents($filename));
		if($crt===FALSE) {
			throw new \RuntimeException("Root certificate ".$filename." is not a valid PEM file.");
		}
		$this->ca = $filename;
	}
	
	public function setCertificateFile(string $filename): void {
		if(!file_exists($filename)) {
			throw new \RuntimeException("Server certificate ".$filename." not found.");
		}
		$crt = @openssl_x509_read(file_get_contents($filename));
		if($crt===FALSE) {
			throw new \RuntimeException("Server certificate ".$filename." is not a valid PEM file.");
		}
		$this->cert = $filename;
		
	}
	
	public function setPrivateKeyFile(string $filename): void {
		if(!file_exists($filename)) {
			throw new \RuntimeException("Server key ".$filename." not found.");
		}
		$key = openssl_pkey_get_private(file_get_contents($filename));
		if($key===FALSE) {
			throw new \RuntimeException("Server key ".$filename." is not a valid PEM file.");
		}
		$this->key = $filename;
	}
	
	public function getCA(): string {
		return $this->ca;
	}
	
	public function getCertificate(): string {
		return $this->cert;
	}
	
	public function getPrivateKey(): string {
		return $this->key;
	}
	
	public function getContextServer(): mixed {
		$context = stream_context_create();
		stream_context_set_option($context, 'ssl', 'local_cert', $this->cert);
		stream_context_set_option($context, 'ssl', 'local_pk', $this->key);
		stream_context_set_option($context, 'ssl', 'cafile', $this->ca);
	return $context;
	}
	
	public function getContextClient(): mixed {
		$context = stream_context_create();
		stream_context_set_option($context, 'ssl', 'cafile', $this->ca);
	return $context;
	}
}
