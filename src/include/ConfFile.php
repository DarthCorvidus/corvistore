<?php
/*
 * ConfFile
 * 
 * I originally wanted to use YAML for configuration, but support for YAML is
 * bad over different distributions. I am looking at YOU, Red Hat Enterprise
 * Linux and CentOS.
 * But in the end, I only need one depth of key/value and lists, for include/
 * exclude in the client configuration.
 */
class ConfFile {
	static function fromFile(string $file): array {
		Assert::fileExists($file);
		$content = file_get_contents($file);
	return self::fromString($content);
	}
	
	static function fromString(string $string): array {
		$exp = explode(PHP_EOL, $string);
		$result = array();
		$current = "";
		$i = 0;
		foreach($exp as $line) {
			$i++;
			if($line=="") {
				continue;
			}
			if($line[0]=="#") {
				continue;
			}
 			$split = explode(":", $line, 2);
			if(!preg_match("/^\s+/", $split[0]) and !isset($split[1])) {
				throw new RuntimeException("invalid key '".$split[0]."' in line ".$i);
			}
			if(isset($split[1]) && $split[1]==="") {
				$result[$split[0]] = array();
				$current = $split[0];
				continue;
			}
			if(count($split)==2) {
				$result[$split[0]] = trim($split[1]);
				$current = "";
				continue;
			}
			if(preg_match("/^\s+/", $split[0]) and $current=="") {
				throw new RuntimeException("isolated list value in line ".$i);
			}

			if(preg_match("/^\s+/", $split[0])) {
				$result[$current][] = trim($split[0]);
				continue;
			}
		}
	return $result;
	}
}
