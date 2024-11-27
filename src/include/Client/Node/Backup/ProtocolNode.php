<?php
namespace Node;
use \Net\ProtocolAsyncListener;
use Net\ProtocolAsync;
use plibv4\process\Scheduler;
class ProtocolNode implements ProtocolAsyncListener, DirectoryWalkObserver {
	private ProtocolAsync $protocol;
	private Scheduler $scheduler;
	private $done = false;
	private $paused = false;
	private $queue = 0;
	private array $files;
	private int $transferred = 0;
	private \plibv4\process\Task $task;
	private BackupStat $stat;
	private \FileGroup $filegroup;
	private bool $iteratorDone = false;
	/**
	 * File size below which a file will be added to a file group (1Mib)
	 */
	const FILE_SIZE_THRESHOLD = 1048576;
	/**
	 * Maximum size to which a FileGroup is allowed to grow until it gets sent
	 * to the server (10Mib)
	 */
	const FILEGROUP_SIZE_MAX = 10485760;
	/**
	 * Maximum amount of files in a FileGroup before it gets sent to the server
	 * (255). 255 was chosen with a binary transfer in mind (uint8).
	 */
	const FILEGROUP_AMOUNT_MAX = 255;
	function __construct() {
		$this->stat = new BackupStat();
		$this->filegroup = new \FileGroup();
	}
	
	public function setProtocol(\Net\ProtocolAsync $protocol) {
		$this->protocol = $protocol;
	}
	
	public function setScheduler(Scheduler $sched) {
		$this->scheduler = $sched;
	}
	
	public function onCommand(\Net\ProtocolAsync $protocol, string $command) {
		echo $command;
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol) {
		//$this->scheduler->terminate();
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message) {
		echo $message.PHP_EOL;
	}

	public function onOk(\Net\ProtocolAsync $protocol) {
		if($this->done == true) {
			$this->protocol->sendCommand("QUIT");
		}
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, $unserialized) {
		$this->queue--;
		if($unserialized instanceof \CatalogEntries) {
			$this->onCatalogEntries($unserialized);
		}
		
		if($this->queue==0 && $this->paused == true) {
			echo "Resuming Iterator with queue entries ".$this->queue.PHP_EOL;
			$this->scheduler->resume($this->task);
			$this->paused = false;
		}
	}
	
	private function onCatalogEntries(\CatalogEntries $catalogEntries) {
		$files = $this->getFiles($catalogEntries->getDirname());
		$diff = $catalogEntries->getDiff($files);
		$this->uploadChanged($catalogEntries, $diff);
		
		$this->uploadNew($catalogEntries, $diff);
		// remove processed 'Files' entry.
		unset($this->files[$catalogEntries->getDirname()]);
		/**
		 * If the iterator has stopped, and no more files need to be processed,
		 * send DONE to server.
		 * Either this or onFiles can send DONE, depending which one has no
		 * Files entries left.
		 */
		if($this->iteratorDone && empty($this->files)) {
			if($this->filegroup->getFileCount()>0) {
				#echo "Sending last filegroup with ".number_format($this->filegroup->getPayloadSize()).PHP_EOL;
				$this->protocol->sendSerialize($this->filegroup);
			}
			$this->protocol->sendCommand("DONE");
			$this->done = true;
		}
	}
	
	private function uploadChanged(\CatalogEntries $catalogEntries, \CatFileDiff $diff) {
		for($i=0;$i<$diff->getChanged()->getCount();$i++) {
			$file = $diff->getChanged()->getEntry($i);
			$entry = $catalogEntries->getByName($file->getBasename());
			$file->setAction(\File::UPDATE);
			$this->protocol->sendCommand("UPDATE FILE ".$entry->getId());
			$this->protocol->sendSerialize($file);
			if($file->getType()==\Catalog::TYPE_FILE) {
				echo "Updating ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendStream(new \Net\FileSender($file));
					$this->stat->incrChangeFile();
					$this->stat->addBytes($file->getSize());
					//$this->transferred += $file->getSize();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}
			if($file->getType()==\Catalog::TYPE_LINK) {
				echo "Sending link ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendStream(new \Net\LinkSender($file));
					//$this->transferred += $file->getSize();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}
		}
	}
	/**
	 * Checks if a file should be added to the current file group.
	 * @param \File $file
	 * @return bool
	 */
	private function eligibleForFilegroup(\File $file): bool {
		/*
		 * Add files below FILE_SIZE_THRESHOLD to FileGroup
		 */
		if($file->getType()== \Catalog::TYPE_FILE && $file->getSize()<= self::FILE_SIZE_THRESHOLD) {
			return true;
		}
		#if($file->getType()== \Catalog::TYPE_DIR) {
		#	return true;
		#}
	return false;
	}
	
	private function uploadNew(\CatalogEntries $catalogEntries, \CatFileDiff $diff) {
		#echo "Uploading for ".$catalogEntries->getDirname().PHP_EOL;
		for($i=0;$i<$diff->getNew()->getCount();$i++) {
			$file = $diff->getNew()->getEntry($i);
			#echo "\tWill send ".$file->getPath().PHP_EOL;
		}
		for($i=0;$i<$diff->getNew()->getCount();$i++) {
			#pcntl_signal_dispatch();
			$file = $diff->getNew()->getEntry($i);
			// Skip files for now.
			#if($file->getType()!= \Catalog::TYPE_DIR) {
			#	continue;
			#}
			#$this->protocol->sendCommand("CREATE FILE ".$file->getPath());
			if($this->eligibleForFilegroup($file)) {
				$file->setAction(\File::CREATE);
				$this->filegroup->addFile($file);
				/**
				 * Send Filegroup to Server if either maximum
				 */
				if($this->filegroup->getFileCount()== self::FILEGROUP_AMOUNT_MAX or $this->filegroup->getPayloadSize()>= self::FILEGROUP_SIZE_MAX) {
					echo "Sending filegroup with ".$this->filegroup->getFileCount()." files [". number_format($this->filegroup->getPayloadSize())."]".PHP_EOL;
					//$this->protocol->sendBinaryClass($this->filegroup);
					$this->protocol->sendSerialize($this->filegroup);
					$this->stat->addNewFile($this->filegroup->getFileCount());
					$this->filegroup = new \FileGroup();
				}
			continue;
			}
			
			$file->setAction(\File::CREATE);
			$this->protocol->sendSerialize($file);
			if($file->getType()== \Catalog::TYPE_FILE) {
			#if($file->getType()== \Catalog::TYPE_FILE) {
				echo "Sending new file ".$file->getPath()." [".number_format($file->getSize())."]".PHP_EOL;
				try {
					$this->protocol->sendStream(new \Net\FileSender($file));
					$this->transferred += $file->getSize();
					$this->stat->addBytes($file->getSize());
					$this->stat->incrNewFile();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}
			
			if($file->getType()== \Catalog::TYPE_LINK) {
				echo "Sending link ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendStream(new \Net\LinkSender($file));
					$this->transferred += $file->getSize();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}

			#$entry = $this->protocol->getSerialized();
			
			#$entry = $this->catalog->newEntry($file, $parent);
			#$catalogEntries->addEntry($entry);
			#print_r($entry);
			#if($entry->getVersions()->getLatest()->getType()==Catalog::TYPE_FILE) {
			#	$this->storage->store($entry->getVersions()->getLatest(), $this->partition, $file);
			#}
		}
	}
	
	private function getFiles(string $dir): \Files {
		return $this->files[$dir];
	}
	

	public function onEnd(\plibv4\process\Task $task): void {
		echo "onEnd with filegroup count ".$this->filegroup->getFileCount().PHP_EOL;
		if($this->filegroup->getFileCount()!=0) {
			echo "onEnd:Sending last filegroup with ".number_format($this->filegroup->getPayloadSize()).PHP_EOL;
			$this->protocol->sendSerialize($this->filegroup);
		}
		$this->iteratorDone = true;
		/**
		 * If there are no more files to be processed, send DONE here.
		 */
		if(empty($this->files)) {
			$this->protocol->sendCommand("DONE");
			$this->done = true;
		}
	}

	public function onFiles(\plibv4\process\Task $task, string $dir, \Files $files): void {
		$this->files[$dir] = $files;
		$this->protocol->sendCommand("GET CATALOG ".$dir);
		$this->queue++;
		if($this->queue>=100 && $this->paused == false) {
			echo "Pausing Iterator with queue entries ".$this->queue.PHP_EOL;
			$this->scheduler->pause($task);
			$this->paused = true;
		}
		$this->task = $task;
	}
	
	public function getBackupStat(): BackupStat {
		return $this->stat;
	}

	public function onBinaryClass(ProtocolAsync $protocol, $instance) {
		
	}
}