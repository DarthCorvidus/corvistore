<?php
/**
 * Storage
 * 
 * Model to handle 'query storage'.
 */
class StorageList implements TerminalTableLayout, TerminalTableModel {
	private $values;
	private $title;
	private $pdo;
	const NAME = 0;
	const TYPE = 1;
	const CAPACITY = 2;
	const USED = 3;
	function __construct(EPDO $pdo) {
		$this->title = array("Name", "Type", "Capacity", "Used");
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
		$stmt = $this->pdo->prepare("select * from d_storage");
		$stmt->execute();
		foreach($stmt as $key => $value) {
			$this->values[$key][self::NAME] = $value["dst_name"];
			$this->values[$key][self::TYPE] = $value["dst_type"];
			$storage = Storage::fromName($this->pdo, $value["dst_name"]);
			$this->values[$key][self::CAPACITY] = number_format($storage->getFree());
			$this->values[$key][self::USED] = number_format($storage->getUsed());
		}
	}

}
