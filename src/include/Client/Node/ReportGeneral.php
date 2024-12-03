<?php
namespace Node;
/**
 * Model for general reporting to be used with TerminalTable.
 *
 * @author Claus-Christoph Küthe
 */
class ReportGeneral implements \TerminalTableModel {
	const NAME = 0;
	const VALUE = 1;
	private array $values = array();
	private array $report = array();
	function __construct(array $report) {
		$this->report = $report;
		
	}
	public function getCell(int $col, int $row): string {
		return $this->values[$row][$col];
	}

	public function getColumns(): int {
		return 2;
	}

	public function getRows(): int {
		return count($this->values);
	}

	public function getTitle(int $col): string {
		return "";
	}

	public function hasTitle(): bool {
		return false;
	}

	public function load(): void {
		$this->values = array();
		$this->values[0] = array("Files:", number_format($this->report["files"], 0));
		$this->values[1] = array("Occupancy:", number_format($this->report["occupancy"], 0));
		$this->values[2] = array("Oldest:", date("Y-m-d H:i:s", $this->report["oldest"]));
		$this->values[3] = array("Newest:", date("Y-m-d H:i:s", $this->report["newest"]));
	}

}
