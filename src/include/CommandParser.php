<?php
declare(strict_types=1);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Command Parser
 * 
 * This class will turn commands into usable bits and pieces.
 *
 * @author Claus-Christoph Küthe
 */
class CommandParser {
	private $raw;
	private $command;
	private $positional;
	private $params;
	function __construct($command) {
		$this->raw = self::split($command);
		$this->command = $this->raw[0];
		if(isset($this->raw[1])) {
			$this->object = $this->raw[1];
		}
		$this->positional = array();
		$this->params = array();
		foreach(array_slice($this->raw, 2) as $key => $value) {
			$split = explode("=", $value, 2);
			if(count($split)==1) {
				$this->positional[] = $value;
				continue;
			}
			$this->params[$split[0]] = $split[1];
		}
	}
	
	static function split(string $command): array {
		$split = array();
		$string = trim($command);
		$part = "";
		$quote = false;
		for($i=0;$i<strlen($string);$i++) {
			$c = $string[$i];

			if($c=='"' && $quote==FALSE) {
				$quote = TRUE;
				continue;
			}

			if($c=='"' && $quote==TRUE) {
				$quote = FALSE;
				continue;
			}
			
			if($quote==TRUE && $c!='"') {
				$part .= $c;
				continue;
			}
			
			if($c==" " && $string[$i-1]==" " && $quote==FALSE) {
				continue;
			}
			if($c==" " && $quote==FALSE) {
				$split[] = $part;
				$part = "";
				continue;
			}
			$part .= $c;
		}
		if($quote==TRUE) {
			throw new Exception("Malformed command, open quote not closed.");
		}
		$split[] = $part;
	return $split;
	}
	
	function getCommand() {
		return $this->command;
	}
	
	function getObject() {
		return $this->object;
	}
	
	function getPositional($id) {
		return $this->positional[$id];
	}
	
	function getParam($param) {
		return $this->params[$param];
	}
}
