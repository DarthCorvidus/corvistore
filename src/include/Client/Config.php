<?php
namespace Client;
/**
 * Does the import of the client configuration.
 *
 * @author Claus-Christoph Küthe
 */
class Config implements \ImportModel {
	private $import;
	private $values;
	function __construct($path) {
		if(!file_exists($path)) {
			throw new \RuntimeException("Client configuration at ".$path." not available.");
		}
		if(!is_file($path)) {
			throw new \RuntimeException("Client configuration at ".$path." not a file.");
		}
		$yaml = yaml_parse_file($path);
		$this->import = new \Import($yaml, $this);
		$this->values = $this->import->getArray();
	}

	function getNode(): string {
		return $this->values["node"];
	}
	
	function getExclude(): array {
		if(!isset($this->values["exclude"])) {
			return array();
		}
		return $this->values["exclude"];
	}
	
	function getInclude(): array {
		if(!isset($this->values["include"])) {
			return array();
		}
		return $this->values["include"];
	}
	
	function getInEx(): \InEx {
		$inex = new \InEx();
		foreach($this->getInclude() as $value) {
			$inex->addInclude($value);
		}
		foreach($this->getExclude() as $value) {
			$inex->addExclude($value);
		}
	return $inex;
	}

	function getHost(): string {
		return $this->values["host"];
	}
	
	public function getImportListModel($name): \ImportModel {
		
	}

	public function getImportListNames(): array {
		return array();
	}

	public function getImportModel($name): \ImportModel {
		
	}

	public function getImportNames(): array {
		return array();
	}

	public function getScalarListModel($name): \UserValue {
		if($name=="exclude" or $name=="include") {
			return \UserValue::asOptional();
		}
	}

	public function getScalarListNames(): array {
		return array("include", "exclude");
	}

	public function getScalarModel($name): \UserValue {
		if($name=="node" or $name=="host") {
			$userValue = \UserValue::asMandatory();
			return $userValue;
		}
	}

	public function getScalarNames(): array {
		return array("node", "host");
	}

}
