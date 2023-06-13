<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Versions
 *
 * @author hm
 */
class Versions {
	private $version = array();
	function __construct() {
		;
	}
	
	function addVersion(VersionEntry $version) {
		$this->version[] = $version;
	}
	
	function getCount(): int {
		return count($this->version);
	}
	
	function getVersion(int $id): VersionEntry {
		return $this->version[$id];
	}
	
	function getLatest(): VersionEntry {
		return $this->version[$this->getCount()-1];
	}
	
	function filterToTimestamp(int $timestamp): Versions {
		echo date("Y-m-d H:i:s", $timestamp).PHP_EOL;
		$new = new Versions();
		for($i=0; $i<$this->getCount();$i++) {
			$version = $this->getVersion($i);
			if($version->getCreated()>$timestamp) {
				return $new;
			}
			$new->addVersion($version);
		}
	return $new;
	}
	
	function toBinary(): string {
		$string = "";
		$string .= IntVal::uint16LE()->putValue($this->getCount());
		for($i=0;$i<$this->getCount();$i++) {
			$string .= $this->getVersion($i)->toBinary();
		}
	return $string;
	}
	
	static function fromBinary($string): Versions {
		$versions = new Versions();
		$amount = IntVal::uint16LE()->getValue(substr($string, 0, 2));
		$rest = substr($string, 2);
		for($i=0;$i<$amount;$i++) {
			$version = VersionEntry::fromBinary($rest);
			$versions->addVersion($version);
			/*
			 * This is terribly ugly, but there is no way for me to know how
			 * long the part was that was used by the previous call of
			 * fromBinary.
			 */
			$rest = substr($rest, strlen($version->toBinary()));
		}
	return $versions;
	}
}
