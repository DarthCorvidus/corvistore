<?php
namespace Node;
use Net\ProtocolAsyncListener;
class RestoreProtocolListener implements ProtocolAsyncListener {
	private \ArgvRestore $argvRestore;
	private string $restoreSource;
	private string $restoreTarget;
	private array $breadcrumbs = array();
	private int $timestamp;
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
		echo $classname.PHP_EOL;
		throw new \RuntimeException("not implemented onBinaryClass");
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
		}
		if($unserialized::class === \CatalogEntries::class) {
			$this->onCatalogEntries($protocol, $unserialized);
		}
		
	}
	
	private function restoreBreadcrumb(\Net\ProtocolAsync $protocol, \CatalogEntry $entry): void {
		#$path = array_shift($this->breadcrumbs);
		$path = $entry->getDirname();
		$this->restoreDirectory($path, $entry);
		array_shift($this->breadcrumbs);
		if(empty($this->breadcrumbs)) {
			echo "Sending GET CATALOG ".$entry->getDirname().$entry->getName()."/".PHP_EOL;
			$protocol->sendCommand("GET CATALOG ".$entry->getDirname().$entry->getName());
		return;
		}
		#echo "Sending GET PATH ".$this->breadcrumbs[0].PHP_EOL;
		$protocol->sendCommand("GET PATH ".$this->breadcrumbs[0]);
	}
	
	private function restoreDirectory(string $path, \CatalogEntry $entry): void {
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
		$protocol->sendCommand("QUIT");
	}
		
}