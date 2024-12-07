<?php
namespace Node;
/**
 * Report handles report request for a node, which will either display a general
 * report of the node or a detailed report for a file.
 *
 * @author Claus-Christoph Küthe
 */
class Report {
	/** @var list<string> */
	private array $argv;
	private \Net\ProtocolSync $protocol;
	/**
	 * 
	 * @param \Net\ProtocolSync $protocol
	 * @param \Client\Config $config
	 * @param list<string> $argv as initialized by PHP from CLI parameters
	 */
	function __construct(\Net\ProtocolSync $protocol, \Client\Config $config, array $argv) {
		$this->argv = $argv;
		$this->protocol = $protocol;
	}
	
	private function runGeneral(): void {
		$this->protocol->sendCommand("REPORT");
		$report = $this->protocol->getSerialized();
		$model = new ReportGeneral($report);
		$table = new \TerminalTable($model);
		#echo "Report for node ".$this->node->getName().":".PHP_EOL;
		$table->printTable();
	}
	
	private function runPath(): void {
		$argvReport = new \ArgvReport();
		$argv = new \Argv($this->argv, $argvReport);
		
		$this->protocol->sendCommand("REPORT ".$argvReport->getPositionalArg(1)->getValue());
		if(substr($argvReport->getPositionalArg(1)->getValue(), -1)=="/") {
			$entries = $this->protocol->getSerialized();
			$model = new ReportDirectory($entries, $argv);
		} else {
			$entry = $this->protocol->getSerialized();
			$model = new \ReportFile($entry, $argv);
		}
		$table = new \TerminalTable($model);
		echo "Report for ".$this->argv[2].":".PHP_EOL;
		$table->printTable();
	}
	
	function run(): void {
		if(!isset($this->argv[2])) {
			$this->runGeneral();
		} else {
			$this->runPath();
		}
		$this->protocol->sendCommand("QUIT");
	}
}
