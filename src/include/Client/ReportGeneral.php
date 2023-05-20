<?php
/**
 * Model for general reporting to be used with TerminalTable.
 *
 * @author Claus-Christoph Küthe
 */
class ReportGeneral implements \TerminalTableModel {
	const NAME = 0;
	const VALUE = 1;
	private $values;
	private $node;
	private $pdo;
	function __construct(EPDO $pdo, Node $node) {
		$this->node = $node;
		$this->pdo = $pdo;
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
		
	}

	public function hasTitle(): bool {
		return false;
	}

	private function gatherOccupancy(): int {
		$params[] = $this->node->getId();
		$params[] = 1;
		$size = $this->pdo->result("select sum(dvs_size) from d_catalog JOIN d_version USING (dc_id) JOIN n_version2basic USING (dvs_id) WHERE dnd_id = ? and dvs_stored = ?", $params);
	return $size;
	}
	
	public function load() {
		$this->values = array();
		$files = $this->pdo->result("select count(dc_id) from d_catalog where dnd_id = ?", array($this->node->getId()));
		$this->values[0] = array("Files:", number_format($files, 0));
		$this->values[1] = array("Occupancy:", number_format($this->gatherOccupancy(), 0));
		$epochOldest = $this->pdo->result("select min(dvs_created_epoch) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ?", array($this->node->getId()));
		$this->values[2] = array("Oldest:", date("Y-m-d H:i:s", $epochOldest));
		$epochNewest = $this->pdo->result("select max(dvs_created_epoch) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ?", array($this->node->getId()));
		$this->values[3] = array("Newest:", date("Y-m-d H:i:s", $epochNewest));
	}

}
