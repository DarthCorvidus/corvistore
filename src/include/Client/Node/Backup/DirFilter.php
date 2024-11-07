<?php
/**
 * 
 */
namespace Node;
class DirFilter extends \RecursiveFilterIterator {
	private \InEx $inex;
	function __construct(\RecursiveIterator $iterator, \InEx $inex) {
		$this->inex = $inex;
		return parent::__construct($iterator);
	}
	public function accept(): bool {
		$fileInfo = $this->current();
		$path = $fileInfo->getRealPath();
		if($fileInfo->isLink()) {
			$path = str_replace("//", "/", $fileInfo->getPathname());
		}
		if($this->inex->isValid($path)) {
			return true;
		} else {
			echo "Skipping ".$this->current()->getRealPath().PHP_EOL;
			return false;
		}
		return $this->inex->isValid($this->current()->getRealPath());
		if(in_array($this->current()->getRealPath(), $this->exclude, true)) {
			return false;
		}
		return true;
	}
	
	public function getChildren(): ?\RecursiveFilterIterator {
		return new DirFilter($this->current()->getChildren(), $this->inex);
	}
}