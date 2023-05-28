<?php
/**
 * Not really necessary, but looks a tad bit nicer to have the differences
 * between the filesystem / the catalog tucked away in one class.
 */
class CatFileDiff {
	private $new;
	private $deleted;
	private $changed;
	function __construct() {
		$this->new = new Files();
		$this->changed = new Files();
		$this->deleted = new CatalogEntries();
	}
	
	function addNew(File $file) {
		$this->new->addEntry($file);
	}
	
	function getNew(): Files {
		return $this->new;
	}
	
	function addChanged(File $file) {
		$this->changed->addEntry($file);
	}
	
	function getChanged(): Files {
		return $this->changed;
	}
	
	function addDeleted(CatalogEntry $catalog) {
		$this->deleted->addEntry($catalog);
	}
	
	function getDeleted(): CatalogEntries {
		return $this->deleted;
	}
}
