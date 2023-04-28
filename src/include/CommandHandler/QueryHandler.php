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
	
	function getStorageList() {
		$result = "";
		$model = new StorageList($this->pdo);
		$table = new TerminalTable($model);
		foreach($table->getLines() as $key => $value) {
			$result .= $value.PHP_EOL;
		}
	return $result;
	}

	function getPartitionList() {
		$result = "";
		$model = new PartitionList($this->pdo);
		$table = new TerminalTable($model);
		foreach($table->getLines() as $key => $value) {
			$result .= $value.PHP_EOL;
		}
	return $result;
	}
	
	
	
	function getResult() {
		if($this->command->getObject()=="storage") {
			return $this->getStorageList();
		}
		if($this->command->getObject()=="partition") {
			return $this->getPartitionList();
		}
		throw new Exception("query ".$this->command->getObject()." is not a valid query.");
	}
	
	function run() {
		try {
			echo $this->getResult();
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}
