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
		$yaml = yaml_parse_file($path);
		$this->import = new \Import($yaml, $this);
		$this->values = $this->import->getArray();
	}
	
	function getNode() {
		return $this->values["node"];
	}
	
	function getExclude() {
		return $this->values["exclude"];
	}
	
	function getInclude() {
		return $this->values["include"];
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
		if($name=="node") {
			$userValue = \UserValue::asMandatory();
			return $userValue;
		}
	}

	public function getScalarNames(): array {
		return array("node");
	}

}
