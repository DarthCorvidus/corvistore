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

	public function store(VersionEntry $entry, Partition $partition, File $file) {
		$new["dvs_id"] = $entry->getId();
		$new["dst_id"] = $this->getId();
		$new["dpt_id"] = $partition->getId();
		$new["nvb_stored"] = 0;
		$id = $this->pdo->create("n_version2basic", $new);
		$location = $this->getPathForIdLocation($id);
		if(!file_exists($location)) {
			mkdir($location, 0700, true);
		}
		if(!copy($file->getPath(), $this->getPathForIdFile($id))) {
			throw new Exception("file could not be copied");
		}
		$this->pdo->update("n_version2basic", array("nvb_stored"=>1), array("nvb_id"=>$id));
		$entry->setStored($this->pdo);
	}
	
	public function restore(VersionEntry $entry, string $target) {
		$param[] = $entry->getId();
		$param[] = $this->getId();
		#$param[] = $this->getPartitionId();
		$param[] = 1;
		$result = $this->pdo->row("select nvb_id from n_version2basic where dvs_id = ? and dst_id = ? and nvb_stored = ? limit 1", $param);
		$path = $this->getPathForIdFile($result["nvb_id"]);
		if(!copy($path, $target)) {
			throw new Exception("file could not be copied");
		}
	}
	
	function getReadHandle(Partition $partition, int $versionId) {
		$param[] = $this->getId();
		$param[] = $partition->getId();
		$param[] = $versionId;
		$param[] = 1;
		$result = $this->pdo->result("select nvb_id from n_version2basic where dst_id = ? and dpt_id = ? and dvs_id = ? and nvb_stored = ? limit 1", $param);
		$path = $this->getPathForIdFile($result);
		#echo "Source Path: ".$path.PHP_EOL;
	return fopen($path, "r");
	}
	
	function storeFromHandle(Partition $partition, int $versionId, $handle) {
		$new["dvs_id"] = $versionId;
		$new["dst_id"] = $this->getId();
		$new["dpt_id"] = $partition->getId();
		$new["nvb_stored"] = 0;
		$id = $this->pdo->create("n_version2basic", $new);
		$path = $this->getPathForIdFile($id);
		$location = $this->getPathForIdLocation($id);
		#echo "Target Path: ".$path.PHP_EOL;
		if(!file_exists($location)) {
			mkdir($location, 0700, true);
		}
		$wh = fopen($path, "w");
		if($wh==FALSE) {
			throw new Exception("could not open ".$path);
		}
		while($read = fread($handle, 10*1024*1024)) {
			fwrite($wh, $read);
		}
		$this->pdo->update("n_version2basic", array("nvb_stored"=>1), array("nvb_id"=>$id));
		fclose($wh);
		fclose($handle);
	}
	
	public function getStoredVersions(Partition $partition): array {
		$result = array();
		$param[] = $this->getId();
		$param[] = $partition->getId();
		$param[] = 1;
		$stmt = $this->pdo->prepare("select dvs_id from d_version JOIN n_version2basic USING (dvs_id) where dst_id = ? and dpt_id = ? and nvb_stored = ?");
		$stmt->execute($param);
		foreach($stmt as $key => $value) {
			$result[] = $value["dvs_id"];
		}
	return $result;
	}
	
}
