<?php
namespace Node;
use Net\ProtocolAsyncListener;
class RestoreProtocolListener implements ProtocolAsyncListener {
	private \ArgvRestore $argvRestore;
	private string $restoreSource;
	private string $restoreTarget;
	private array $breadcrumbs = array();
	private int $timestamp;
	/** @var list<\CatalogEntry> */
	private array $queue = array();
	public function __construct(\ArgvRestore $argvRestore) {
		$this->restoreSource = $argvRestore->getRestorePath();
		$this->restoreTarget = $argvRestore->getTargetPath();
		if($this->restoreSource != "/") {
			$this->breadcrumbs = \Shared::getBreadcrumbs($this->restoreSource);
		}
		$this->argvRestore = $argvRestore;
		$this->timestamp = strtotime($this->argvRestore->getTimestamp());
	}
	
	public function start(\Net\ProtocolAsync $protocol): void {
		if(empty($this->breadcrumbs)) {
			echo "Sending GET PATH /".PHP_EOL;
			$protocol->sendCommand("GET PATH /");
		} else {
			echo "Sending GET PATH ".$this->breadcrumbs[0].PHP_EOL;
			$protocol->sendCommand("GET PATH ".$this->breadcrumbs[0]);
		}
	}

	public function onBinaryClass(\Net\ProtocolAsync $protocol, string $classname, string $classdata): void {
		if($classname === \FileTransportContainer::class) {
			$this->onBinaryClassFTC($classdata);
		return;
		}
		throw new \RuntimeException("not implemented onBinaryClass");
	}
	
	private function onBinaryClassFTC(string $classdata): void {
		$ftc = \FileTransportContainer::fromBinary($classdata);
		$restorePath = $this->restoreTarget."".$ftc->getFile()->getPath();
		if($ftc->getFile()->getType() === \Catalog::TYPE_FILE) {
			#echo "Restoring FTC file to ".$restorePath.PHP_EOL;
			file_put_contents($restorePath, $ftc->getData());
			$ftc->getFile()->restoreMeta($restorePath);
		}
		if($ftc->getFile()->getType() === \Catalog::TYPE_LINK) {
			#echo "Restoring FTC link to ".$restorePath.PHP_EOL;
			symlink($ftc->getData(), $restorePath);
		}
	}
	

	public function onCommand(\Net\ProtocolAsync $protocol, string $command): void {
		echo $command.PHP_EOL;
		throw new \RuntimeException("not implemented onCommand");
	}

	public function onDisconnect(\Net\ProtocolAsync $protocol): void {
		throw new \RuntimeException("not implemented onDisconnect");
	}

	public function onMessage(\Net\ProtocolAsync $protocol, string $message): void {
		echo "Got: ".$message.PHP_EOL;
		echo "Sending quit due to unexpected Message.".PHP_EOL;
		$protocol->sendCommand("QUIT");
	}

	public function onOk(\Net\ProtocolAsync $protocol): void {
		throw new \RuntimeException("not implemented onOk");
	}

	public function onSerialized(\Net\ProtocolAsync $protocol, mixed $unserialized): void {
		if($unserialized::class === \CatalogEntry::class && !empty($this->breadcrumbs)) {
			$this->restoreBreadcrumb($protocol, $unserialized);
		return;
		}
		if($unserialized::class === \CatalogEntries::class) {
			$this->onCatalogEntries($protocol, $unserialized);
		return;
		}
	throw new \RuntimeException("I do not know how to react to ".$unserialized::class);
	}
	
	private function restoreBreadcrumb(\Net\ProtocolAsync $protocol, \CatalogEntry $entry): void {
		$path = $entry->getDirname();
		$this->restoreDirectory($entry);
		array_shift($this->breadcrumbs);
		if(empty($this->breadcrumbs)) {
			echo "Sending GET CATALOG ".$entry->getDirname().$entry->getName()."/".PHP_EOL;
			$protocol->sendCommand("GET CATALOG ".$entry->getDirname().$entry->getName());
		return;
		}
		#echo "Sending GET PATH ".$this->breadcrumbs[0].PHP_EOL;
		$protocol->sendCommand("GET PATH ".$this->breadcrumbs[0]);
	}
	
	private function restoreDirectory(\CatalogEntry $entry): void {
		# We have to filter again.
		$version = $entry->getVersions()->filterToTimestamp($this->timestamp)->getLatest();
		$catalogDirname = $entry->getDirname();
		if($catalogDirname === "/") {
			$catalogDirname = "";
		}
		$filepath = $this->restoreTarget.$catalogDirname."/".$entry->getName();
		if(!file_exists($filepath)) {
			echo "Restoring directory ".$filepath.PHP_EOL;
			mkdir($filepath, $version->getPermissions(), true);
			chown($filepath, $version->getOwner());
			chgrp($filepath, $version->getGroup());
			return;
		}
	}
	
	private function onCatalogEntries(\Net\ProtocolAsync $protocol, \CatalogEntries $entries): void {
		$files = \Files::fromDirectory($this->restoreTarget.$entries->getDirname());
		$diff = $entries->getDiff($files);
		$missing = $diff->getServerOnly();
		//$different = $diff->getChanged();
		for($i=0;$i<$missing->getCount();$i++) {
			$entry = $missing->getEntry($i);
			$versions = $entry->getVersions()->filterToTimestamp($this->timestamp);
			if($versions->getCount()===0) {
				continue;
			}
			$latest = $versions->getLatest();
			if($latest->getType() === \Catalog::TYPE_DIR) {
				$this->restoreDirectory($entry);
				// Put directories to a queue
				$this->queue[] = $entry;
			continue;
			}
			//echo "Requesting ".$entry->getDirnameTrailed().$entry->getName()." with version id ".$latest->getId().PHP_EOL;
			$protocol->sendCommand("GET VERSION ".$latest->getId());
		}
		/*
		 * call continue to send a query for the next catalog; this approach
		 * allows for a kind of rate limiting.
		 */
		$this->continue($protocol);
	}
	
	private function getNext(): \CatalogEntry {
		return array_shift($this->queue);
	}
	
	private function continue(\Net\ProtocolAsync $protocol): void {
		if(empty($this->queue)) {
			$protocol->sendCommand("QUIT");
		return;
		}
		$next = $this->getNext();
		$query = $next->getDirname()."/".$next->getName();
		echo "Querying server for catalog of ".$query." (Queue: ".count($this->queue).")".PHP_EOL;
		$protocol->sendCommand("GET CATALOG ".$query);
	}
		
}