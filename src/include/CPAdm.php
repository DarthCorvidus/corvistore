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
	function __construct() {
		;
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
		echo "I don't know to handle anything yet.".PHP_EOL;
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
