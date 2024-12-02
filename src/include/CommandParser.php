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
	private array $raw = array();
	private string $command;
	private string $object;
	private array $positional;
	private array $params;
	private array $posSanitized = array();
	private array $paramsSanitized = array();
	private bool $imported = false;
	function __construct(string $command) {
		$this->raw = self::split($command);
		$this->command = $this->raw[0];
		$this->object = "";
		if(isset($this->raw[1])) {
			$this->object = $this->raw[1];
		}
		$this->positional = array();
		$this->params = array();
		foreach(array_slice($this->raw, 2) as $value) {
			$split = explode("=", $value, 2);
			if(count($split)==1) {
				$this->positional[] = $value;
				continue;
			}
			$this->params[$split[0]] = $split[1];
		}
	}
	
	private function validateParams(CPModel $model): void {
		$user = array_keys($this->params);
		$allowed = $model->getParameters();
		// array_diff keeps the indexes, but I need new indexes.
		$diff_theirs = array_values(array_diff($user, $allowed));
		/*
		 * In both checks, the first invalid parameter fails.
		 */
		if(!empty($diff_theirs)) {
			throw new InvalidArgumentException(sprintf("Parameter '%s' not valid for '%s %s'", $diff_theirs[0], $this->command, $this->object));
		}
		$diff_ours = array_diff($allowed, $user);
		foreach($model->getParameters() as $value) {
			$uservalue = $model->getParamUserValue($value);
			
			/*
			 * This is a little bit ugly, but we want to have a different message
			 * when the parameter was not set by the user as opposed to an empty
			 * parameter (ie parameter=).
			 */
			try {
				if(!isset($this->params[$value])) {
					$this->paramsSanitized[$value] = $uservalue->getValue();
					continue;
				}
			} catch(MandatoryException $e) {
				//T
				throw new InvalidArgumentException(sprintf("Mandatory parameter '%s' is missing.", $value));
			}
			
			try {
				$uservalue->setValue($this->params[$value]);
				$this->paramsSanitized[$value] = $uservalue->getValue();
			} catch(MandatoryException $e) {
				throw new InvalidArgumentException("Error at parameter '".$value."': ".$e->getMessage());
			} catch(ValidateException $e) {
				throw new InvalidArgumentException("Error at parameter '".$value."': ".$e->getMessage());
			}
		}
	}
	
	private function validatePositional(CPModel $model): void {
		if(count($this->positional)>$model->getPositionalCount()) {
			throw new InvalidArgumentException(sprintf("Unexpected positional value '%s' for '%s %s'", $this->positional[$model->getPositionalCount()], $this->command, $this->object));
		}
		if(count($this->positional)<$model->getPositionalCount()) {
			throw new InvalidArgumentException(sprintf("Missing positional value %d for '%s %s'", $model->getPositionalCount()-count($this->positional), $this->command, $this->object));
		}
		for($i=0;$i<$model->getPositionalCount();$i++) {
			$userValue = $model->getPositionalUserValue($i);
			$userValue->setValue($this->positional[$i]);
			$this->posSanitized[] = $userValue;
		}
	}
	
	function import(CPModel $model): void {
		$this->posSanitized = array();
		$this->paramsSanitized = array();
		$this->validateParams($model);
		$this->validatePositional($model);
		$this->imported = true;
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
	
	function getCommand(): string {
		return $this->command;
	}
	
	function getObject(): string {
		return $this->object;
	}
	
	function getPositional(int $id): string {
		if($this->imported === false) {
			throw new RuntimeException(sprintf("Accessing positional parameter '%d' without calling CommandParser::import()", $id));
		}
		return $this->posSanitized[$id]->getValue();
	}
	
	function getParam(string $param): string {
		if($this->imported === false) {
			throw new RuntimeException(sprintf("Accessing named parameter '%s' without calling CommandParser::import()", $param));
		}
		return $this->paramsSanitized[$param];
	}
}
