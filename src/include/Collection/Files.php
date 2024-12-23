<?php
class Files {
	/** @var list<File> */
	private array $entries = array();
	/** @var array<string, int> */
	private array $names = array();
	function __construct() {
		
	}
	/**
	 * 
	 * @param string $path
	 * @param InEx $inex
	 * @return \Files
	 */
	static function fromDirectory(string $path, \InEx $inex = null): \Files {
		$files = new Files();
		foreach(glob($path."/{,.}*", GLOB_BRACE) as $value) {
			$object = new \SplFileInfo($value);
			if($object->getBasename()==="." or $object->getBasename()==="..") {
				continue;
			}
			$realPath = $object->getRealPath();
			if($inex!=null and !$inex->isValid($realPath)) {
				continue;
			}
			/*
			 * Skip early if file is link.
			 */
			if($object->isLink()) {
				$file = \File::fromPath($value);
				$files->addEntry($file);
				continue;
			}
			if(!$object->isDir() && !$object->isFile()) {
				#echo "Skipping unknown file type ".$realPath.PHP_EOL;
				continue;
			}
			
			try {
				$file = \File::fromPath($value);
				$files->addEntry($file);
			} catch(\Exception $e) {
				#echo $e::class.PHP_EOL;
				echo $e->getMessage().PHP_EOL;
			}
		}
	return $files;
	}
	
	function addEntry(File $file): void {
		$this->entries[] = $file;
		$this->names[$file->getBasename()] = $this->getCount()-1;
	}
	
	function getCount(): int {
		return count($this->entries);
	}
	
	function getEntry(int $id): File {
		return $this->entries[$id];
	}
	
	function hasName(string $name): bool {
		return isset($this->names[$name]);
	}
	
	function getByName(string $name): File {
		return $this->entries[$this->names[$name]];
	}
	
	function getDirectories(): Files {
		$files = new Files();
		foreach($this->entries as $value) {
			if($value->getType() == Catalog::TYPE_DIR) {
				$files->addEntry($value);
			}
		}
	return $files;
	}
}