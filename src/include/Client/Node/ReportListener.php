<?php
namespace Node;
class ReportListener implements \Net\ProtocolReactiveListener {
	private $argv;
	function __construct(array $argv) {
		$argvReport = new \ArgvReport($argv);
		$this->argv = new \Argv($argv, $argvReport);
	}
	public function onCommand(\Net\ProtocolReactive $protocol, string $command) {
		
	}

	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $message) {
		
	}

	public function onOk(\Net\ProtocolReactive $protocol) {
		if(!$this->argv->hasPositional(1)) {
			$protocol->sendCommand("report");
		} else {
			$protocol->sendCommand("report ".$this->argv->getPositional(1));
		}
	}

	public function onSerialized(\Net\ProtocolReactive $protocol, $unserialized) {
		if(!$this->argv->hasPositional(1)) {
			$report = new \Node\ReportGeneral($unserialized);
			$table = new \TerminalTable($report);
			echo $table->printTable();
			$protocol->sendCommand("quit");
			exit();
		}
		if(substr($this->argv->getPositional(1), -1)=="/") {
			$model = new ReportDirectory($unserialized, $this->argv);
		} else {
			$model = new \ReportFile($unserialized, $this->argv);
		}
		$table = new \TerminalTable($model);
		echo $table->printTable();
		$protocol->sendCommand("quit");
		exit();

	}
}
