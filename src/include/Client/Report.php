<?php
/**
 * Report handles report request for a node, which will either display a general
 * report of the node or a detailed report for a file.
 *
 * @author Claus-Christoph Küthe
 */
class Report {
	private $node;
	private $pdo;
	private $argv;
function __construct(EPDO $pdo, Client\Config $config, array $argv) {
		$this->pdo = $pdo;
		$this->node = Node::fromName($this->pdo, $config->getNode());
	}
	
	private function runGeneral() {
		$model = new ReportGeneral($this->pdo, $this->node);
		$table = new TerminalTable($model);
		echo "Report for node ".$this->node->getName().":".PHP_EOL;
		$table->printTable();
	}
	
	function run() {
		if(empty($this->argv[2])) {
			$this->runGeneral();
		}
	}
}
