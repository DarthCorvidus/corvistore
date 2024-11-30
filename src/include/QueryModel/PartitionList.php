<?php
/**
 * PartitionList
 * 
 * List all partitions, including Storage 
 *
 * @author Claus-Christoph Küther
 */
class PartitionList implements TerminalTableLayout, TerminalTableModel {
	private array $values = [];
	private array $title = [];
	private EPDO $pdo;
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

	/**
	 * @psalm-suppress MissingReturnType
	 */
	public function load() {
		$this->values = array();
		$stmt = $this->pdo->prepare("select * from d_partition JOIN d_storage USING (dst_id)");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		foreach($stmt as $key => $value) {
			$this->values[$key][self::PARTITION] = $value["dpt_name"];
			$this->values[$key][self::STORAGE] = $value["dst_name"];
			$this->values[$key][self::TYPE] = $value["dpt_type"];
			$partition = Partition::fromArray($this->pdo, $value);
			$storage = Storage::fromId($this->pdo, $partition->getStorageId());
			$this->values[$key][self::CAPACITY] = number_format($storage->getFree());
			$this->values[$key][self::USED] = number_format($storage->getUsed($partition));
		}
	}

}
