<?php
/**
 * Model for file reporting to be used with TerminalTable.
 *
 * @author Claus-Christoph Küthe
 */
class ReportFile implements \TerminalTableModel, TerminalTableLayout {
	const COUNT = 0;
	const TYPE = 1;
	const BACKUP = 2;
	const SIZE = 3;
	const OWNER = 4;
	const GROUP = 5;
	const PERM = 6;
	const MAX = 7;
	private $values = array();
	private $catEntry;
	private $title = array();
	private $timestamp;
	function __construct(CatalogEntry $entry, Argv $argv) {
		$this->catEntry = $entry;
		$this->title = array_fill(0, self::MAX, "");
		$this->title[self::TYPE] = "Type";
		$this->title[self::BACKUP] = "Backup";
		$this->title[self::SIZE] = "Size";
		$this->title[self::OWNER] = "Owner";
		$this->title[self::GROUP] = "Group";
		$this->title[self::PERM] = "Access";
		$this->timestamp = strtotime($argv->getValue("date")." ".$argv->getValue("time"));
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

	private function getType(int $type): string {
		if($type==0) {
			return "DEL";
		}
		if($type==1) {
			return "DIR";
		}
		if($type==2) {
			return "FILE";
		}
	}
	
	public function load() {
		$versions = $this->catEntry->getVersions()->filterToTimestamp($this->timestamp);
		$sum = 0;
		for($i=0; $i<$versions->getCount();$i++) {
			$version = $this->catEntry->getVersions()->getVersion($i);
			$entry = array_fill(0, self::MAX, "");
			$entry[self::COUNT] = $i+1;
			$entry[self::TYPE] = $this->getType($version->getType());
			$entry[self::BACKUP] = date("Y-m-d H:i:s", $version->getCreated());
			$entry[self::SIZE] = number_format($version->getSize());
			$entry[self::OWNER] = $version->getOwner();
			$entry[self::GROUP] = $version->getGroup();
			$entry[self::PERM] = $version->getPermissionsNice();
			$sum += $version->getSize();
			$this->values[] = $entry;
		}
		$entry = array_fill(0, self::MAX, "");
		$entry[self::SIZE] = number_format($sum);
		$this->values[] = $entry;
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
		if($col==self::COUNT) {
			return self::RIGHT;
		}
		if($col==self::SIZE) {
			return self::RIGHT;
		}
	return self::LEFT;
	}

}
