<?php
namespace Node;
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
	private $protocol;
	function __construct(\Client\Config $config, array $argv) {
		#$this->pdo = $pdo;
		#$this->node = \Node::fromName($this->pdo, $config->getNode());
		$this->argv = $argv;
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_connect($socket, $config->getHost(), 4096);
		socket_write($socket, "node ".$config->getNode()."\n");
		$this->protocol = new \Net\Protocol($socket);

	}
	
	private function runGeneral() {
		$this->protocol->sendCommand("REPORT");
		$report = $this->protocol->getUnserializePHP();
		$model = new ReportGeneral($report);
		$table = new \TerminalTable($model);
		#echo "Report for node ".$this->node->getName().":".PHP_EOL;
		$table->printTable();
	}
	
	private function runPath() {
		$argvReport = new \ArgvReport();
		$argv = new \Argv($this->argv, $argvReport);
		
		$this->protocol->sendCommand("REPORT ".$argvReport->getPositionalArg(1)->getValue());
		if(substr($argvReport->getPositionalArg(1)->getValue(), -1)=="/") {
			$entries = $this->protocol->getUnserializePHP();
			$model = new ReportDirectory($entries, $argv);
		} else {
			$entry = $this->protocol->getUnserializePHP();
			$model = new \ReportFile($entry, $argv);
		}
		$table = new \TerminalTable($model);
		echo "Report for ".$this->argv[2].":".PHP_EOL;
		$table->printTable();
	}
	
	function run() {
		if(empty($this->argv[2])) {
			$this->runGeneral();
		} else {
			$this->runPath();
		}
		$this->protocol->sendCommand("QUIT");
	}
}
