<?php
/**
 * Not really necessary, but looks a tad bit nicer to have the differences
 * between the filesystem / the catalog tucked away in one class.
 */
class CatFileDiff {
	private Files $new;
	private CatalogEntries $deleted;
	private Files $changed;
	function __construct(string $dirname) {
		$this->new = new Files();
		$this->changed = new Files();
		$this->deleted = new CatalogEntries($dirname);
	}
	
	function addNew(File $file): void {
		$this->new->addEntry($file);
	}
	
	function getNew(): Files {
		return $this->new;
	}
	
	function addChanged(File $file): void {
		$this->changed->addEntry($file);
	}
	
	function getChanged(): Files {
		return $this->changed;
	}
	
	function addDeleted(CatalogEntry $catalog): void {
		$this->deleted->addEntry($catalog);
	}
	
	function getDeleted(): CatalogEntries {
		return $this->deleted;
	}
}
