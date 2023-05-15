<?php
/**
 * Class to handle inclusion and exclusion of directories. No complex rules yet.
 * @author Claus-Christoph Küthe
 */

class InEx {
	private $exclude;
	private $include;
	function __construct() {
		;
	}
	
	private function normalize(string $string) {
		$convert = new ConvertTrailingSlash(ConvertTrailingSlash::REMOVE);
	return $convert->convert($string);
	}
	
	function addExclude(string $exclude) {
		$this->exclude[] = $this->normalize($exclude);
	}
	
	function addInclude(string $include) {
		$this->include[] = $this->normalize($include);
	}
	
	function isIncluded(string $path) {
		if(empty($this->include)) {
			return TRUE;
		}
		$path = $this->normalize($path);
		foreach($this->include as $key => $value) {
			if(preg_match("/^". preg_quote($value, "/")."/", $path)) {
				return true;
			}
		}
	return FALSE;
	}
	
	function isExcluded(string $path): bool {
		if(empty($this->exclude)) {
			return FALSE;
		}
		$path = $this->normalize($path);
		foreach($this->exclude as $key => $value) {
			if(preg_match("/^". preg_quote($value, "/")."/", $path)) {
				return true;
			}
		}
	return FALSE;
	}
	
	/**
	 * If a certain path is included, we must be allowed to transit all directories
	 * to reach that path, but we must not backup their contents.
	 * @param type $path
	 * @return boolean
	 */
	function transitOnly($path) {
		foreach($this->include as $key => $value) {
			if($path==$value) {
				return FALSE;
			}
			if(preg_match("/^". preg_quote($path, "/")."/", $value)) {
				return true;
			}
		}
	return FALSE;
	}
	
	function isValid($path) {
		$included = $this->isIncluded($path);
		$excluded = $this->isExcluded($path);
		#echo $path.PHP_EOL;
		#echo "Ex: ".$excluded.PHP_EOL;
		#echo "In: ".$included.PHP_EOL;
		if($included == FALSE) {
			return FALSE;
		}
		if($included == TRUE and $excluded == TRUE) {
			return FALSE;
		}
		if($included == TRUE and $excluded == TRUE) {
			return FALSE;
		}

	return TRUE;
	}
}