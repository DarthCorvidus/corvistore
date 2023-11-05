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
class StorageBasic extends Storage implements \Net\StreamReceiver {
	private $versionEntry;
	private $partition;
	private $writeHandle;
	private $storeId;
	private $recvSize;
	private $recvLeft;
	private $file;
	private $sem;
	function __construct() {
		parent::__construct();
		$this->sem = sem_get(posix_getppid());
		
	}
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

	public function store(VersionEntry $entry, Partition $partition, File $file): \Net\StreamReceiver {
		$this->versionEntry = $entry;
		$this->partition = $partition;
		$this->file = $file;
	return $this;
	}
	
	public function restore(int $version): \Net\StreamSender {
		$param[] = $version;
		#$param[] = $this->getPartitionId();
		$param[] = 1;
		$result = $this->pdo->row("select dco_serial from d_content where dvs_id = ? and dco_stored = ? limit 1", $param);
		$path = $this->getPathForIdFile($result["dco_serial"]);
		$fileSender = new \Net\FileSender(File::fromPath($path), 8192);
	return $fileSender;
	}
	
	public function onRecvCancel() {
		echo "Transfer cancelled, cleaning up.".PHP_EOL;
		if(file_exists($this->getPathForIdFile($this->storeId))) {
			unlink($this->getPathForIdFile($this->storeId));
		}
		$this->pdo->delete("d_content", array("dco_id"=>$this->storeId));
		$this->partition = NULL;
		$this->file = NULL;
		$this->versionEntry = NULL;
		$this->storeId = NULL;
		fclose($this->writeHandle);
	}
	
	public function setRecvSize(int $size) {
		$this->recvSize = $size;
		$this->recvLeft = $size;
	}
	
	public function getRecvSize():int {
		return $this->recvSize;
	}
	
	function getRecvLeft(): int {
		return $this->recvLeft;
	}
	
	public function receiveData(string $data) {
		fwrite($this->writeHandle, $data);
		$this->recvLeft -= strlen($data);
	}

	public function onRecvEnd() {
		$this->pdo->update("d_content", array("dco_stored"=>1), array("dco_id"=>$this->storeId));
		$this->versionEntry->setStored($this->pdo);
		$this->partition = NULL;
		$this->file = NULL;
		$this->versionEntry = NULL;
		$this->storeId = NULL;
		fclose($this->writeHandle);
	}

	public function onFail() {
		$this->partition = NULL;
		$this->versionEntry = NULL;
		$this->storeId = NULL;
		fclose($this->writeHandle);
	}

	public function onRecvStart() {
		// First try on sem_acquire will not block.
		while(sem_acquire($this->sem, TRUE)===FALSE) {
			// Show debug message here.
			echo "Mutex for Process ".posix_getpid().PHP_EOL;
			// Block until semaphore is acquired, then quit.
			sem_acquire($this->sem);
			break;
		}
		$param = array();
		$param[] = $this->getId();
		$serial = $this->pdo->result("select coalesce(max(dco_serial), 0)+1 from d_content where dst_id = ?", $param);
		$new["dvs_id"] = $this->versionEntry->getId();
		$new["dst_id"] = $this->getId();
		$new["dpt_id"] = $this->partition->getId();
		$new["dco_serial"] = $serial;
		$new["dco_stored"] = 0;
		$this->storeId = $this->pdo->create("d_content", $new);
		sem_release($this->sem);
		
		$path = $this->getPathForIdFile($serial);
		$location = $this->getPathForIdLocation($serial);
		#echo "Target Path: ".$path.PHP_EOL;
		if(!file_exists($location)) {
			mkdir($location, 0700, true);
		}
		$this->writeHandle = fopen($path, "w");
		fwrite($this->writeHandle, str_pad($this->file->toBinary(), 8192, "\0"));
		if($this->writeHandle==FALSE) {
			throw new Exception("could not open ".$path);
		}
		#echo "Writing to ".$path.PHP_EOL;
	}

	public function getFree(): int {
		return disk_free_space($this->location);
	}

	public function getUsed(\Partition $partition = NULL): int {
		if($partition==NULL) {
			$param[] = 1;
			$param[] = $this->id;
			return $this->pdo->result("select coalesce(sum(dvs_size), 0) from d_version JOIN d_content USING (dvs_id) where dvs_stored = ? and dst_id = ?", $param);
		} else {
			$param[] = 1;
			$param[] = $this->id;
			$param[] = $partition->getId();
			return $this->pdo->result("select coalesce(sum(dvs_size), 0) from d_version JOIN d_content USING (dvs_id) where dvs_stored = ? and dst_id = ? and dpt_id = ?", $param);
		}
	}

}
