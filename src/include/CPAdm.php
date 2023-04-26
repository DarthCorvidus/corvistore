<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Administrative Console for Crow Protect
 *
 * @author Claus-Christoph Küthe
 */
class CPAdm {
	private $pdo;
	private $shared;
	function __construct(EPDO $pdo) {
		$this->pdo = $pdo;
	}

	function printWelcome() {
		echo "Crow Protect version less than pre-alpha".PHP_EOL;
		echo "(c) Claus-Christoph Küthe 2023".PHP_EOL;
	}
	
	function getCommand($input = ""): CommandParser {
		while($input=="") {
			echo "cpmad> ";
			$input = trim(fgets(STDIN));
		}
	return new CommandParser($input);
	}
	
	function handleCommand(CommandParser $command) {
		if($command->getCommand()=="query") {
			$query = new QueryHandler($this->pdo, $command);
			$query->run();
			return;
		}
		if($command->getCommand()=="define") {
			$query = new DefineHandler($this->pdo, $command);
			$query->run();
			return;
		}
		echo "Invalid command '".$command->getCommand()."'".PHP_EOL;
	}
	
	function run() {
		$this->printWelcome();
		while(true) {
			$command = $this->getCommand();
			if($command->getCommand()=="quit") {
				return;
			}
			$this->handleCommand($command);
		}
	}
}
