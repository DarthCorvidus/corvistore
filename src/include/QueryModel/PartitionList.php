<?php
/**
 * PartitionList
 * 
 * List all partitions, including Storage 
 *
 * @author Claus-Christoph Küther
 */
class PartitionList implements TerminalTableLayout, TerminalTableModel {
	private $values;
	private $title;
	private $pdo;
	const PARTITION = 0;
	const STORAGE = 1;
	const TYPE = 2;
	const CAPACITY = 3;
	const USED = 4;
	function __construct(EPDO $pdo) {
		$this->title = array("Name", "Storage", "Type", "Capacity", "Used");
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
		if($col==self::CAPACITY or $col==self::USED) {
			return self::RIGHT;
		}
		return self::LEFT;
	}

	public function getCell(int $col, int $row): string {
		return $this->values[$row][$col];
	}

	public function getColumns(): int {
		return count($this->title);
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

	public function load() {
		$this->values = array();
		$stmt = $this->pdo->prepare("select * from d_partition JOIN d_storage USING (dst_id)");
		$stmt->execute();
		foreach($stmt as $key => $value) {
			$this->values[$key][self::PARTITION] = $value["dpt_name"];
			$this->values[$key][self::STORAGE] = $value["dst_name"];
			$this->values[$key][self::TYPE] = $value["dpt_type"];
			$this->values[$key][self::CAPACITY] = "0 GiB";
			$this->values[$key][self::USED] = "0 GiB";
		}
	}

}
