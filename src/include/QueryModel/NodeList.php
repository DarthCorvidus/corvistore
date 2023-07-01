<?php
/**
 * Storage
 * 
 * Model to handle 'query storage'.
 */
class NodeList implements TerminalTableLayout, TerminalTableModel {
	private $values;
	private $title;
	private $pdo;
	const NAME = 0;
	const FILES = 1;
	const VERSIONS = 2;
	const SPACE = 3;
	const POLICY = 4;
	const MAX = 5;
	function __construct(EPDO $pdo) {
		$this->title = array("Name", "Files", "Space", "Versions", "Policy");
		$this->pdo = $pdo;
	}

	public function getCellAttr(int $col, int $row): array {
		return array();
	}

	public function getCellBack(int $col, int $row): int {
		return 0;
	}

	public function getCellFore(int $col, int $row): int {
		return 0;
	}

	public function getCellJustify(int $col, int $row): int {
		if($col==self::FILES or $col==self::SPACE or $col==self::VERSIONS) {
			return self::RIGHT;
		}
		return self::LEFT;
	}

	public function getCell(int $col, int $row): string {
		return $this->values[$row][$col];
	}

	public function getColumns(): int {
		return self::MAX;
	}

	public function getRows(): int {
		return count($this->values);
	}

	public function getTitle(int $col): string {
		return $this->title[$col];
	}

	public function hasTitle(): bool {
		return true;
	}
	
	public function getFiles(int $nodeId): int {
		return $this->pdo->result("select coalesce(count(*), 0) from d_catalog where dnd_id = ?", array($nodeId));
	}
	
	public function getSpace(int $nodeId): int {
		return $this->pdo->result("select coalesce(sum(dvs_size), 0) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ? and dvs_stored = ?", array($nodeId, 1));
	}
	
	public function getVersions(int $nodeId): int {
		return $this->pdo->result("select coalesce(count(dvs_id), 0) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ? and dvs_stored = ?", array($nodeId, 1));
	}
	
	
	public function load() {
		$this->values = array();
		$stmt = $this->pdo->prepare("select * from d_node LEFT JOIN d_policy USING (dpo_id)");
		$stmt->execute();
		foreach($stmt as $key => $value) {
			$entry = array_fill(0, self::MAX, "");
			$entry[self::NAME] = $value["dnd_name"];
			$entry[self::FILES] = number_format($this->getFiles($value["dnd_id"]));
			$entry[self::SPACE] = number_format($this->getSpace($value["dnd_id"]));
			$entry[self::VERSIONS] = number_format($this->getVersions($value["dnd_id"]));
			$entry[self::POLICY] = $value["dpo_name"];
			$this->values[] = $entry;
		}
	}

}
