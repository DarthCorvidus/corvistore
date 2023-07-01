<?php
/**
 * Query
 * 
 * Collection of static methods which will handle 'query' commands, delegating
 * them to the respective classes.
 *
 * @author Claus-Christoph Küthe
 */
class QueryHandler {
	private $command;
	private $pdo;
	function __construct(EPDO $pdo, CommandParser $command) {
		$this->pdo = $pdo;
		$this->command = $command;
	}
	
	function getStorageList(): string {
		$result = "";
		$model = new StorageList($this->pdo);
		$table = new TerminalTable($model);
	return $table->getString();
	}

	function getPartitionList(): string {
		$result = array();
		$model = new PartitionList($this->pdo);
		$table = new TerminalTable($model);
	return $table->getString();
	}
	
	function getNodeList(): string {
		$result = array();
		$model = new NodeList($this->pdo);
		$table = new TerminalTable($model);
	return $table->getString();
	}
	
	
	function getResult(): string  {
		if($this->command->getObject()=="storage") {
			return $this->getStorageList();
		}
		if($this->command->getObject()=="partition") {
			return $this->getPartitionList();
		}
		
		if($this->command->getObject()=="node") {
			return $this->getNodeList();
		}
		throw new Exception("query ".$this->command->getObject()." is not a valid query.");
	}
	
	function run(): string {
		return $this->getResult();
	}
}
