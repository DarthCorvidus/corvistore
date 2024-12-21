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
use Storage\StorageBasicContext;
class StorageBasic extends Storage implements \Net\StreamReceiver {
	private ?StorageBasicContext $context;
	private mixed $writeHandle;
	#private mixed $sem;
	function __construct() {
		parent::__construct();
		#$this->sem = sem_get(posix_getppid());
	}
	
	private function assertContext(): void {
		// 'this should not happen'
		if($this->context === null) {
			throw new \RuntimeException(self::class." not properly initialized to receive stream data");
		}
	}
	
	static function getHexArray(int $id): array {
		$hex = str_pad(dechex($id), 16, 0, STR_PAD_LEFT);
		$grouped = array();
		for($i=0;$i<8;$i++) {
			$grouped[] = $hex[$i*2].$hex[($i*2)+1];
		}
	return $grouped;
	}
	
	function getPathForIdFile(int $id): string {
		$hexArray = self::getHexArray($id);
		return $this->location."/".implode("/", array_slice($hexArray, 0, 7))."/".$hexArray[7].".cp";
	}

	function getPathForIdLocation(int $id): string {
		$hexArray = self::getHexArray($id);
		return $this->location."/".implode("/", array_slice($hexArray, 0, 7))."/";
	}

	public function store(VersionEntry $entry, Partition $partition, File $file): \Net\StreamReceiver {
		$this->context = new StorageBasicContext($file, $partition, $entry);
	return $this;
	}
	
	public function storeSingle(\Storage\StorageJob $job): void {
		/**
		 * Using a transaction here speeds up SQLite.
		 */
		$this->pdo->beginTransaction();
		$entry = $job->catalog->newEntry($job->file);
		$versionEntry = $entry->getVersions()->getLatest();
		$job->file->setServerCreated($versionEntry->getCreated());
		$job->file->setServerNodeName($job->node->getName());
		$job->file->setServerVersionId($versionEntry->getId());
		$job->file->setServerStoreType(\File::BACK_MAIN);

		$serial = $this->getSerial();
		$storeId = $this->getStoreId($versionEntry, $job->partition, $serial);
		
		$path = $this->getPathForIdFile($serial);
		$location = $this->getPathForIdLocation($serial);
		#echo "Target Path: ".$path.PHP_EOL;
		if(!file_exists($location)) {
			mkdir($location, 0700, true);
		}
		$data = str_pad($job->file->toBinary(), 8192, "\0");
		$data .= $job->filedata;
		error_clear_last();
		file_put_contents($path, $data);
		$error = error_get_last();
		if(!empty($error)) {
			throw new \Exception($error["message"]);
		}
		$this->endStore($versionEntry, $storeId);
		$this->pdo->commit();
	}
	
	/**
	 * 
	 * @param VersionEntry $version Do not create VersionEntry from ID alone if ID comes from untrusted source (ie client)
	 * @return \Net\StreamSender
	 */
	public function restore(VersionEntry $version): \Net\StreamSender {
		$param = array();
		$param[] = $version->getId();
		$param[] = 1;
		$result = $this->pdo->row("select dco_serial from d_content where dvs_id = ? and dco_stored = ? limit 1", $param);
		$path = $this->getPathForIdFile($result["dco_serial"]);
		$fileSender = new \Net\FileSender(File::fromPath($path), 0);
	return $fileSender;
	}

	/**
	 * 
	 * @param VersionEntry $version Do not create VersionEntry from ID alone if ID comes from untrusted source (ie client)
	 * @return \Net\StreamSender
	 */
	public function restoreSingle(\VersionEntry $version): \FileTransportContainer {
		$param = array();
		$param[] = $version->getId();
		$param[] = 1;
		$result = $this->pdo->row("select dco_serial from d_content where dvs_id = ? and dco_stored = ? limit 1", $param);
		$path = $this->getPathForIdFile($result["dco_serial"]);
		$ftc = \FileTransportContainer::fromStoredFile(file_get_contents($path));
	return $ftc;
	}
	
	public function onRecvCancel(): void {
		if($this->context === null) {
			throw new \RuntimeException("storage context missing");
		}
		echo "Transfer cancelled, cleaning up.".PHP_EOL;
		if(file_exists($this->getPathForIdFile($this->context->getStoreId()))) {
			unlink($this->getPathForIdFile($this->context->getStoreId()));
		}
		$this->pdo->delete("d_content", array("dco_id"=>$this->context->getStoreId()));
		$this->context = null;
		fclose($this->writeHandle);
	}
	
	public function setRecvSize(int $size): void {
		//Not necessary, as size is determined by context.
		#$this->recvSize = $size;
		#$this->recvLeft = $size;
	}
	
	public function getRecvSize():int {
		$this->assertContext();
		return $this->context->getSize();
	}
	
	function getRecvLeft(): int {
		$this->assertContext();
		return $this->context->getLeft();
	}
	
	public function receiveData(string $data): void {
		$this->assertContext();
		fwrite($this->writeHandle, $data);
		$this->context->subtractLeft(strlen($data));
	}

	public function onRecvEnd(): void {
		$this->assertContext();
		$this->endStore($this->context->getVersionEntry(), $this->context->getStoreId());
		$this->context = null;
		fclose($this->writeHandle);
	}

	private function getSerial(): int {
		$param = array();
		$param[] = $this->getId();
		$serial = $this->pdo->result("select coalesce(max(dco_serial), 0)+1 from d_content where dst_id = ?", $param);
	return $serial;
	}
	
	private function getStoreId(VersionEntry $versionEntry, Partition $partition, int $serial): int {
		$new = [];
		$new["dvs_id"] = $versionEntry->getId();
		$new["dst_id"] = $this->getId();
		$new["dpt_id"] = $partition->getId();
		$new["dco_serial"] = $serial;
		$new["dco_stored"] = 0;
		#plibv4\profiler\Profiler::startTimer("getStoreId");
		$storeId = $this->pdo->create("d_content", $new);
		#plibv4\profiler\Profiler::endTimer("getStoreId");
	return $storeId;
	}

	private function endStore(VersionEntry $entry, int $storeId): void {
		#plibv4\profiler\Profiler::startTimer("finalizeStored");
		$this->pdo->update("d_content", array("dco_stored"=>1), array("dco_id"=>$storeId));
		$entry->setStored($this->pdo);
		#plibv4\profiler\Profiler::endTimer("finalizeStored");
	}
	
	
	public function onRecvStart(): void {
		if($this->context === null) {
			throw new \RuntimeException("storage context missing");
		}
		// First try on sem_acquire will not block.
		#while(sem_acquire($this->sem, TRUE)===FALSE) {
		#	// Show debug message here.
		#	echo "Mutex for Process ".posix_getpid().PHP_EOL;
		#	// Block until semaphore is acquired, then quit.
		#	sem_acquire($this->sem);
		#	break;
		#}
		$serial = $this->getSerial();
		$storeId = $this->getStoreId($this->context->getVersionEntry(), $this->context->getPartition(), $serial);
		$this->context->setStoreId($storeId);
		
		#sem_release($this->sem);
		
		$path = $this->getPathForIdFile($serial);
		$location = $this->getPathForIdLocation($serial);
		#echo "Target Path: ".$path.PHP_EOL;
		if(!file_exists($location)) {
			mkdir($location, 0700, true);
		}
		$this->writeHandle = fopen($path, "w");
		$header = $this->context->getFile()->toBinary();
		
		fwrite($this->writeHandle, str_pad($header, 8192, "\0"));
		if($this->writeHandle==FALSE) {
			throw new Exception("could not open ".$path);
		}
		#echo "Writing to ".$path.PHP_EOL;
	}

	public function getFree(): int {
		$free = disk_free_space($this->location);
		if($free === false) {
			throw new \RuntimeException("unable to determine free disk space at ".$this->location);
		}
	return (int)disk_free_space($this->location);
	}

	public function getUsed(\Partition $partition = NULL): int {
		$param = array();
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
