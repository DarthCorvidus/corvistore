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
}
