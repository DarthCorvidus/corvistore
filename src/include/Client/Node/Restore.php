<?php
namespace Node;
/*
 * Restore from the server as a client.
 */
class Restore {
	private \ArgvRestore $argv;
	private string $target;
	private int $restored = 0;
	private int $ignored = 0;
	private int $size = 0;
	/** @psalm-suppress PropertyNotSetInConstructor */
	private \Net\ProtocolSync $protocol;
	/** @psalm-suppress PropertyNotSetInConstructor */
	private \Net\ProtocolAsync $protocolNew;
	private int $timestamp;
	private ReplaceQuery $replaceOlder;
	private ?string $replaceEqual = NULL;
	private ReplaceQuery $replaceNewer;
	private ReplaceQuery $replaceSmaller;
	private ReplaceQuery $replaceLarger;
	private \InEx $inex;
	private \Client\Config $config;
	/** @psalm-suppress PropertyNotSetInConstructor */
	private RestoreProtocolListener $restoreProtocolListener;
	/** @psalm-suppress PropertyNotSetInConstructor */
	private \Net\AsyncStream $asyncStream;
	/** @psalm-suppress PropertyNotSetInConstructor */
	private \plibv4\process\Scheduler $scheduler;
	private int $start = 0;
	private RestoreTask $restoreTask;
	/**
	 * 
	 * @param \Net\ProtocolSync $protocol
	 * @param \Client\Config $config Client configuration
	 * @param list<string> $argv as initialized by PHP when run from CLI
	 */
	function __construct(mixed $socket, \Client\Config $config, array $argv) {
		$this->start = hrtime(true);
		$this->config = $config;
		$this->argv = new \ArgvRestore($argv);
		$this->inex = $config->getInEx();
		$this->target = $this->argv->getTargetPath();
		
		$this->constructNew($socket);
		
		$this->timestamp = strtotime($this->argv->getTimestamp());
		$this->replaceNewer = new ReplaceQuery("%s exists but is newer than backup. Action:");
		$this->replaceOlder = new ReplaceQuery("%s exists, but is older than backup. Action:");
		
		$this->replaceSmaller = new ReplaceQuery("%s exists but is smaller than backup. Action:");
		$this->replaceLarger = new ReplaceQuery("%s exists, but is larger than backup. Action:");
		if($this->argv->getSkip()) {
			$this->replaceOlder->setDefault("s");
			$this->replaceNewer->setDefault("s");
			$this->replaceLarger->setDefault("s");
			$this->replaceSmaller->setDefault("s");
		}
		
		/*
		 * As long as the product is in a „pre alpha state“, in-place restores
		 * are disabled, to prevent data loss due to a failed test run.
		 */
		if($this->target=="" or $this->target=="/") {
			echo "in place restore is not yet supported.".PHP_EOL;
			exit();
		}
	}
	
	private function constructOld(mixed $socket): void {
		$this->protocol = new \Net\ProtocolSync(new \Net\StreamClient($socket));
	}
	
	private function constructNew(mixed $socket): void {
		$this->restoreProtocolListener = new RestoreProtocolListener($this->argv);
		$this->protocolNew = new \Net\ProtocolAsync($this->restoreProtocolListener);
		
		$this->protocolNew->setFileReceiver(new \Net\FileReceiverNew($this->argv->getTargetPath()));
		$this->asyncStream = new \Net\AsyncStream($socket);
		$this->asyncStream->setProtocol($this->protocolNew);
		
		$this->restoreTask = new RestoreTask($this->argv, $this->protocolNew, $this->restoreProtocolListener);
		$this->scheduler = new \plibv4\process\Timeshare();
		$this->scheduler->addTask($this->asyncStream);
		$this->scheduler->addTask($this->restoreTask);
	}
	
	function recurseCatalog(string $path): void {
		$this->protocol->sendCommand("GET CATALOG ".$path);
		$entries = $this->protocol->getSerialized();
		$directories = array();
		$links = array();
		$files = array();
		for($i=0;$i<$entries->getCount();$i++) {
			$entry = $entries->getEntry($i);
			// get Versions filtered by Timestamp.
			$versions = $entry->getVersions()->filterToTimestamp($this->timestamp);
			// If there are no versions for a given timestamp, abort.
			if($versions->getCount()==0) {
				continue;
			}
			$latest = $versions->getLatest();
			if($latest->getType()==\Catalog::TYPE_DIR) {
				$directories[] = $entry;
			}
			if($latest->getType()==\Catalog::TYPE_FILE) {
				$files[] = $entry;
			}
			if($latest->getType()==\Catalog::TYPE_LINK) {
				$links[] = $entry;
			}
		}
		foreach($directories as $value) {
			$this->restoreDirectory($path."/", $value);
		}
		foreach($files as $value) {
			$this->restoreFile($path."/", $value);
		}
		foreach($links as $value) {
			$this->restoreLink($path."/", $value);
		}
		
		foreach($directories as $value) {
			$this->recurseCatalog($path."/".$value->getName());
		}
	}

	private function restoreDirectory(string $path, \CatalogEntry $entry): void {
		# We have to filter again.
		$version = $entry->getVersions()->filterToTimestamp($this->timestamp)->getLatest();
		$filepath = $this->target.$path.$entry->getName();
		if(!file_exists($filepath)) {
			echo "Restoring directory ".$filepath.PHP_EOL;
			mkdir($filepath, $version->getPermissions(), true);
			chown($filepath, $version->getOwner());
			chgrp($filepath, $version->getGroup());
			return;
		}
	}
	
	private function restoreLink(string $path, \CatalogEntry $entry): void {
		$version = $entry->getVersions()->filterToTimestamp($this->timestamp)->getLatest();
		$filepath = $this->target.$path.$entry->getName();
		if(is_link($filepath)) {
			echo "Link ".$filepath." exists.".PHP_EOL;
		return;
		}
		#echo "Restore link ".$entry->getDirname()."/".$entry->getName().PHP_EOL;
		$this->protocol->sendCommand("GET VERSION ".$version->getId());
		/*
		 * I opted against having Restore implement TransferListener; 
		 * I prefer to be sure to get a clean slate on each restore.
		 */
		$restoreListener = new \Net\StringReceiver();
		$this->protocol->getStream($restoreListener);
		#echo $restoreListener->getString().PHP_EOL;
		echo "Restore link ".$filepath." → ".$this->target.$path.$restoreListener->getString().PHP_EOL;
		#symlink($this->target.$restoreListener->getString(), $filepath);
		symlink($restoreListener->getString(), $filepath);
		#chown($filepath, $version->getOwner());
		#chgrp($filepath, $version->getGroup());
		#touch($filepath, $version->getMtime());
	}
	
	private function restoreFile(string $path, \CatalogEntry $entry): void {
		# We have to filter again.
		$version = $entry->getVersions()->filterToTimestamp($this->timestamp)->getLatest();
		$filepath = $this->target.$path.$entry->getName();
		$replace = false;
		try {
			if(file_exists($filepath)) {
				// The most likely case is that a file exists, but is newer.
				if(filemtime($filepath)>$version->getMtime() && !$this->replaceNewer->replace($filepath)) {
					echo "Skipping on user intervention".PHP_EOL;
					$this->ignored++;
				return;
				}

				if(filemtime($filepath)<$version->getMtime() && !$this->replaceOlder->replace($filepath)) {
					$this->ignored++;
					return;
				}


				if(filesize($filepath)>$version->getSize() && !$this->replaceLarger->replace($filepath)) {
					$this->ignored++;
					return;
				}
				if(filesize($filepath)>$version->getSize() && !$this->replaceSmaller->replace($filepath)) {
					$this->ignored++;
					return;
				}

				// Currently, I see no use in replacing files with the same timestamp.
				if(filemtime($filepath)==$version->getMtime()) {
					$this->ignored++;
				return;
				}

				#if(filemtime($filepath)>$version->getMtime() && $this->queryReplaceNewer($filepath)=="s") {
				#	$this->ignored++;
				#return;
				#}
			$replace = true;
			}
		} catch(\Exception $e) {
			echo "Aborting on user request.".PHP_EOL;
			$this->displaySummary();
			$this->protocol->sendCommand("QUIT");
			exit(0);
		}
		echo "Restore file to ".$filepath.PHP_EOL;
		$this->protocol->sendCommand("GET VERSION ".$version->getId());
		/*
		 * I opted against having Restore implement TransferListener; 
		 * I prefer to be sure to get a clean slate on each restore.
		 */
		$restoreListener = new \Net\FileReceiver($filepath, $replace);
		$this->protocol->getStream($restoreListener);
		chown($filepath, $version->getOwner());
		chgrp($filepath, $version->getGroup());
		/*
		 * This is one of the very rare occurrences I found a bug in PHP:
		 * chown and chgrp reset any sticky bit, so we have to call chmod
		 * last.
		 */
		chmod($filepath, $version->getPermissions());
		touch($filepath, $version->getMtime());
		##echo $filepath." missing, would restored".PHP_EOL;
		$this->restored++;
		$this->size += $version->getSize();
	return;
	}
	
	private function restoreHierarchy(): void {
		
	}

	private function restoreParents(): string {
		$exp = explode("/", $this->argv->getRestorePath());
		$previous = "/";
		foreach($exp as $key => $value) {
			if($value==NULL) {
				continue;
			}
			$this->protocol->sendCommand("GET PATH ".$previous.$value);
			$entry = $this->protocol->getSerialized();
			$versions = $entry->getVersions()->filterToTimestamp($this->timestamp);
			if($versions->getLatest()->getType()== \Catalog::TYPE_DIR) {
				#echo $parentpath.PHP_EOL;
				#echo $entry->getName().PHP_EOL;
				$this->restoreDirectory($previous, $entry);
			}
			$previous .= $value."/";
		}
		$cv = new \ConvertTrailingSlash(\ConvertTrailingSlash::REMOVE);
	return $cv->convert($previous);
	}
	
	private function displaySummary(): void {
		echo "Restored:    ".$this->restored.PHP_EOL;
		echo "Ignored:     ".$this->ignored.PHP_EOL;
		echo "Transferred: ".number_format($this->size)." Bytes".PHP_EOL;
	}
	
	public function runOld(): void {
		#echo $this->argv->getRestorePath().PHP_EOL;
		if($this->argv->getRestorePath()=="/") {
			$this->recurseCatalog("/");
		} else {
			/**
			 * When a restore path is deeper below root, the path leading to the
			 * part to be restored has to be created as well. This could of
			 * course just be done by mkdir, but then the path would have
			 * root.root as path. Therefore, Restore::restoreParents will be
			 * used to follow the restore path to the point where a restore is
			 * wished.
			 */
			// Get requested path/backup.
			$this->protocol->sendCommand("GET PATH ".$this->argv->getRestorePath());
			$targetEntry = $this->protocol->getSerialized();
			// quit with error message if no version exists on or before timestamp.
			$targetVersions = $targetEntry->getVersions()->filterToTimestamp($this->timestamp);
			if($targetVersions->getCount()==0) {
				$this->protocol->sendCommand("QUIT");
				throw new \Exception($this->argv->getRestorePath()." does not exist in backup before ".date("Y-m-d H:i:s", $this->timestamp));
			}
			// restore all paths leading to the desired target
			$parentpath = $this->restoreParents();
			// if the target is a file, restore single file and quit.
			if($targetVersions->getLatest()->getType()== \Catalog::TYPE_FILE) {
				$this->restoreFile(dirname($this->argv->getRestorePath())."/", $targetEntry);
				$this->displaySummary();
				$this->protocol->sendCommand("QUIT");
				return;
			}
			// if target is a folder, recurse folder
			if($targetVersions->getLatest()->getType()== \Catalog::TYPE_DIR) {
				echo $parentpath.PHP_EOL;
				$this->recurseCatalog($parentpath);
			}
		}
		$this->displaySummary();
		$this->protocol->sendCommand("QUIT");
	}

	public function runNew(): void {
		//$this->restoreProtocolListener->start($this->protocolNew);
		$this->scheduler->run();
	}
	function run(): void {
		$this->runNew();
		$now = hrtime(true);
		echo "Time spent: ".(($now-$this->start)/1000000000).PHP_EOL;
	}
}
