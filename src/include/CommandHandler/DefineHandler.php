<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * DefineHandler
 * 
 * Handler to handle 'define' command.
 *
 * @author Claus-Christoph Küthe
 */
class DefineHandler {
	private $pdo;
	private $command;
	function __construct(EPDO $pdo, CommandParser $command) {
		$this->pdo = $pdo;
		$this->command = $command;
	}

	function run() {
		if($this->command->getObject()=="storage") {
			Storage::define($this->pdo, $this->command);
			return;
		}
		if($this->command->getObject()=="partition") {
			Partition::define($this->pdo, $this->command);
			return;
		}
		if($this->command->getObject()=="policy") {
			Policy::define($this->pdo, $this->command);
			return;
		}
		if($this->command->getObject()=="node") {
			Node::define($this->pdo, $this->command);
			return;
		}
		
		if($this->command->getObject()=="user") {
			User::define($this->pdo, $this->command);
			return;
		}

		throw new Exception("'define ".$this->command->getObject()."' is not a valid command.");
	}
}
