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
	function __construct() {
		;
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
					$this->transferred += $file->getSize();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}
			if($file->getType()==\Catalog::TYPE_LINK) {
				echo "Sending link ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendStream(new \Net\LinkSender($file));
					$this->transferred += $file->getSize();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}
		}
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
			$file->setAction(\File::CREATE);
			$this->protocol->sendSerialize($file);
			if($file->getType()== \Catalog::TYPE_FILE) {
				echo "Sending new file ".$file->getPath()." [".number_format($file->getSize())."]".PHP_EOL;
				try {
					$this->protocol->sendStream(new \Net\FileSender($file));
					$this->transferred += $file->getSize();
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
		$this->protocol->sendCommand("DONE");
		$this->done = true;
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
}