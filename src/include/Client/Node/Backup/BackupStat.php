<?php
namespace Node;
class BackupStat implements \TerminalTableModel {
	private int $bytes = 0;
	private float $startTime = 0.0;
	private int $processedDirs = 0;
	private int $processedFiles = 0;
	private int $newFiles = 0;
	private int $newDirs = 0;
	private int $updatedFiles = 0;
	private int $updatedDirs = 0;
	private int $deletedFiles = 0;
	private int $deletedDirs = 0;
	private array $values;
	public function __construct() {
		$this->startTime = microtime(true);
	}
	
	public function addBytes(int $bytes): void {
		$this->bytes += $bytes;
	}
	
	public function incrProcDir(): void {
		$this->processedDirs++;
	}
	
	public function incrProcFile(): void {
		$this->processedFiles++;
	}
	
	public function incrNewDir(): void {
		$this->newDirs++;
	}
	
	public function incrNewFile(): void {
		$this->newFiles++;
	}

	public function addNewFile(int $files): void {
		$this->newFiles += $files;
	}

	public function incrChangeDir(): void {
		$this->updatedDirs++;
	}
	
	public function incrChangeFile(): void {
		$this->updatedFiles++;
	}
	
	public function incrDelDir(): void {
		$this->deletedDirs++;
	}
	
	public function incrDelFile(): void {
		$this->deletedFiles++;
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

	public function load() {
		$this->values = array();
		$this->values[] = array("Time:", round(microtime(true)-$this->startTime, 2));
		$this->values[] = array("Directories inspected:", $this->processedDirs);
		$this->values[] = array("Files inspected:", $this->processedFiles);
		$this->values[] = array("Directories created: ", $this->newDirs);
		$this->values[] = array("Files created: ", $this->newFiles);
		$this->values[] = array("Directories updated: ", $this->updatedDirs);
		$this->values[] = array("Files updated: ", $this->updatedFiles);
		$this->values[] = array("Directories deleted: ", $this->deletedDirs);
		$this->values[] = array("Files deleted: ", $this->deletedFiles);
		$this->values[] = array("Bytes sent: ", number_format($this->bytes));
	}
}