<?php
/**
 * StorageBasic
 * 
 * A very basic form of storage, for testing. When storing a file, it gets a
 * ID for a file, turns it into hexadecimal and uses the hexidecimal value to
 * generate seven folders in which 256 files will end up.
 *
 * @author Claus-Christoph Küthe
 */
class StorageBasic extends Storage {
	static function getHexArray(int $id) {
		$hex = str_pad(dechex($id), 16, 0, STR_PAD_LEFT);
		$grouped = array();
		for($i=0;$i<8;$i++) {
			$grouped[] = $hex[$i*2].$hex[($i*2)+1];
		}
	return $grouped;
	}
	
	function getPathForIdFile(int $id) {
		$hexArray = self::getHexArray($id);
		return $this->location."/".implode("/", array_slice($hexArray, 0, 7))."/".$hexArray[7].".cp";
	}

	function getPathForIdLocation(int $id) {
		$hexArray = self::getHexArray($id);
		return $this->location."/".implode("/", array_slice($hexArray, 0, 7))."/";
	}

	public function store(VersionEntry $entry, Partition $partition, SourceObject $obj) {
		$new["dv_id"] = $entry->getId();
		$new["dst_id"] = $this->getId();
		$new["dpt_id"] = $partition->getId();
		$new["nvb_stored"] = 0;
		$id = $this->pdo->create("n_version2basic", $new);
		$location = $this->getPathForIdLocation($id);
		if(!file_exists($location)) {
			mkdir($location, 0700, true);
		}
		if(!copy($obj->getPath(), $this->getPathForIdFile($id))) {
			throw new Exception("file could not be copied");
		}
		$this->pdo->update("n_version2basic", array("nvb_stored"=>1), array("nvb_id"=>$id));
		$entry->setStored($this->pdo);
	}
	
	public function restore(VersionEntry $entry, string $target) {
		$param[] = $entry->getId();
		$param[] = $this->getId();
		$param[] = $this->getPartitionId();
		$param[] = 1;
		$result = $this->pdo->row("select nvb_id from n_version2basic where dv_id = ? and dst_id = ? and dpt_id = ? and nvb_stored = ? limit 1");
		$path = $this->getPathForIdFile($result["dvb_id"]);
		if(!copy($path, $target)) {
			throw new Exception("file could not be copied");
		}
	}

}
