<?php
/**
 * This class represents an object in the Catalog. It is basically an array
 * won't write to the database by itself.
 * As it is supposed to be created directly from database values, it has no
 * constructor as well.
 * 
 * @author Claus-Christoph Küthe
 */
class CatalogEntry {
	private int $id;
	private string $name;
	private string $dirname;
	private ?int $parentId = null;
	private int $nodeId;
	private Versions $versions;
	public function __construct(array $array) {
		$this->versions = new Versions();
		$this->id = (int)$array["dc_id"];
		$this->name = $array["dc_name"];
		$this->dirname = $array["dc_dirname"];
		$this->nodeId = $array["dnd_id"];
		if(isset($array["dc_parent"]) and $array["dc_parent"]!==NULL and $array["dc_parent"]!=="") {
			$this->parentId = (int)$array["dc_parent"];
		}
	}
	static function fromArray(EPDO $pdo, array $array): CatalogEntry {
		$ce = new CatalogEntry($array);
	return $ce;
	}
	
	static function fromId(EPDO $pdo, int $id): CatalogEntry {
		$row = $pdo->row("select * from d_catalog where dc_id = ?", array($id));
		if(empty($row)) {
			throw new RuntimeException(sprintf("No catalog entry with id '%d'", $id));
		}
	return CatalogEntry::fromArray($pdo, $row);
	}
	/*
	static function fromName(EPDO $pdo, Node $node, string $name, CatalogEntry $parent = NULL): CatalogEntry {
		$param = array();
		$param[] = $name;
		$param[] = $node->getId();
		$query = "";
		if($parent==NULL) {
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_name = ? and dnd_id = ? and dc_parent IS NULL ORDER BY dc_id, dvs_created_epoch";
		} else {
			$param[] = $parent->id;
			$query = "select * from d_catalog JOIN d_version USING (dc_id) WHERE dc_name = ? and dnd_id = ? and dc_parent = ? ORDER BY dc_id, dvs_created_epoch";
		}
		$stmt = $pdo->prepare($query);
		$stmt->execute($param);
		foreach($stmt as $key => $value) {
			if($key == 0) {
				$entry = CatalogEntry::fromArray($pdo, $value);
				$entry->addVersion($value);
				continue;
			}
			$entry->addVersion($value);
		}
	return $entry;
	}
	*/
	function addVersion(array $array): void {
		if($array["dc_id"]!=$this->id) {
			throw new Exception("this should not happen, version id ".$array["dc_id"]." != catalog id ".$this->id.".");
		}
		$this->versions->addVersion(VersionEntry::fromArray($array));
	}
	
	function getVersions(): Versions {
		return $this->versions;
	}

	function getId(): int {
		return $this->id;
	}
	
	function getName(): string {
		return $this->name;
	}
	
	function getDirname(): string {
		return $this->dirname;
	}
	
	function getDirnameTrailed(): string {
		if($this->dirname=="/") {
			return "/";
		}
		return $this->dirname."/";
	}
	
	function hasParentId(): bool {
		return $this->parentId !== NULL;
	}
	/**
	 * @psalm-suppress InvalidNullableReturnType
	 * @psalm-suppress NullableReturnStatement
	 * @return int
	 * @throws RuntimeException
	 */
	function getParentId():int {
		if($this->hasParentId()) {
			return $this->parentId;
		}
	throw new RuntimeException("Catalog entry has no parent id.");
	}
	
	function getNodeId(): int {
		return $this->nodeId;
	}
}
