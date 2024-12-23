<?php
/**
 * Not really necessary, but looks a tad bit nicer to have the differences
 * between the filesystem / the catalog tucked away in one class.
 */
class CatFileDiff {
	private Files $clientOnly;
	private CatalogEntries $serverOnly;
	private Files $differentFiles;
	private CatalogEntries $differentCatalogEntries;
	function __construct(string $dirname) {
		$this->clientOnly = new Files();
		$this->differentFiles = new Files();
		$this->differentCatalogEntries = new CatalogEntries($dirname);
		$this->serverOnly = new CatalogEntries($dirname);
	}
	
	function addClientOnly(File $file): void {
		$this->clientOnly->addEntry($file);
	}
	
	function getClientOnly(): Files {
		return $this->clientOnly;
	}
	
	function addDifferent(File $file, CatalogEntry $entry): void {
		$this->differentFiles->addEntry($file);
		$this->differentCatalogEntries->addEntry($entry);
	}
	
	function getDifferentFiles(): Files {
		return $this->differentFiles;
	}
	
	function getDifferentCatalogEntries(): CatalogEntries {
		return $this->differentCatalogEntries;
	}
	
	function addServerOnly(CatalogEntry $catalog): void {
		$this->serverOnly->addEntry($catalog);
	}
	
	function getServerOnly(): CatalogEntries {
		return $this->serverOnly;
	}
}
