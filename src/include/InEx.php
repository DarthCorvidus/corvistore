<?php
/**
 * Class to handle inclusion and exclusion of directories. No complex rules yet.
 * @author Claus-Christoph Küthe
 */

class InEx {
	private array $exclude = array();
	private array $include = array();
	private int $depth = 0;
	private \ConvertTrailingSlash $convert;
	function __construct() {
		$this->convert = new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE);;
	}
	
	function addExclude(string $exclude): void {
		$this->exclude[] = $this->convert->convert($exclude);
	}
	
	function addInclude(string $include): void {
		$this->include[] = $this->convert->convert($include);
	}
	
	function isIncluded(string $path): bool {
		if(empty($this->include)) {
			return true;
		}
		$sanitizedPath = $this->convert->convert($path);
		foreach($this->include as $value) {
			if(preg_match("/^". preg_quote($value, "/")."/", $sanitizedPath)) {
				return true;
			}
		}
	return false;
	}
	
	function isExcluded(string $path): bool {
		if(empty($this->exclude)) {
			return false;
		}
		$sanitizedPath = $this->convert->convert($path);
		foreach($this->exclude as $value) {
			if(preg_match("/^". preg_quote($value, "/")."/", $sanitizedPath)) {
				return true;
			}
		}
	return false;
	}
	
	/**
	 * If a certain path is included, we must be allowed to transit all directories
	 * to reach that path, but we must not backup their contents.
	 * @param string $path
	 * @return bool
	 */
	function transitOnly(string $path): bool {
		foreach($this->include as $value) {
			if($path==$value) {
				return false;
			}
			if(preg_match("/^". preg_quote($path, "/")."/", $value)) {
				return true;
			}
		}
	return false;
	}
	
	function isValid(string $path): bool {
		$included = $this->isIncluded($path);
		$excluded = $this->isExcluded($path);
		#echo $path.PHP_EOL;
		#echo "Ex: ".$excluded.PHP_EOL;
		#echo "In: ".$included.PHP_EOL;
		if($included === false) {
			return false;
		}
		if($excluded === true) {
			return false;
		}
	return true;
	}
}