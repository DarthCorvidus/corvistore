<?php
namespace Node;
class ReportListener implements \Net\ProtocolReactiveListener {
	public function onCommand(\Net\ProtocolReactive $protocol, string $command) {
		
	}

	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $message) {
		
	}

	public function onOk(\Net\ProtocolReactive $protocol) {
		$protocol->sendCommand("report");
	}

	public function onSerialized(\Net\ProtocolReactive $protocol, $unserialized) {
		$report = new \Node\ReportGeneral($unserialized);
		$table = new \TerminalTable($report);
		echo $table->printTable();
		$protocol->sendCommand("quit");
		exit();
	}
}
