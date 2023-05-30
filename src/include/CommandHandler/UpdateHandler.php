<?php
/**
 * DefineHandler
 * 
 * Handler to handle 'define' command.
 *
 * @author Claus-Christoph Küthe
 */
class UpdateHandler {
	private $pdo;
	private $command;
	function __construct(EPDO $pdo, CommandParser $command) {
		$this->pdo = $pdo;
		$this->command = $command;
	}

	function run() {
		#if($this->command->getObject()=="storage") {
		#	Storage::update($this->pdo, $this->command);
		#	return;
		#}
		if($this->command->getObject()=="partition") {
			Partition::update($this->pdo, $this->command);
			return;
		}
		#if($this->command->getObject()=="policy") {
		#	Policy::update($this->pdo, $this->command);
		#	return;
		#}
		#if($this->command->getObject()=="node") {
		#	Node::update($this->pdo, $this->command);
		#	return;
		#}
		throw new Exception("'update ".$this->command->getObject()."' is not a valid command.");
	}
}