<?php
namespace Node;
class BackupListener implements \Net\ProtocolReactiveListener, \Net\ProtocolSendListener {
	private $processed = 0;
	private $currentEntries;
	private $quit = FALSE;
	private $path;
	private $last;
	function __construct(\Client\Config $config, array $argv) {
		$this->config = $config;
		$this->argv = $argv;
		$this->inex = $config->getInEx();
		$this->currentDir = new \File("/");
	}

	public function onCommand(\Net\ProtocolReactive $protocol, string $command) {
		
	}

	public function onDisconnect(\Net\ProtocolReactive $protocol) {
		
	}

	public function onMessage(\Net\ProtocolReactive $protocol, string $message) {
		echo $message;
	}

	public function onOk(\Net\ProtocolReactive $protocol) {
		if($this->quit==TRUE) {
			echo "Calling quit".PHP_EOL;
			exit();
		}
		echo "Received OK".PHP_EOL;
		$this->path[0] = "/";
		$protocol->sendCommand("GET CATALOG 0");
		$protocol->expect(\Net\ProtocolReactive::SERIALIZED_PHP);
	}

	public function onSerialized(\Net\ProtocolReactive $protocol, $unserialized) {
		#echo get_class($unserialized).PHP_EOL;
		if(get_class($unserialized)=="CatalogEntries") {
			$this->onCatalogEntries($protocol, $unserialized);
		}
		if(get_class($unserialized)=="CatalogEntry") {
			$this->onCatalogEntry($protocol, $unserialized);
		}
	}
	
	public function onCatalogEntries(\Net\ProtocolReactive $protocol, \CatalogEntries $entries) {
		$parentId = $entries->getParentId();
		
		$files = $this->readDirectory($this->path[$parentId]);
		$diff = $entries->getDiff($files);
		for($i=0;$i<$entries->getCount();$i++) {
			$entry = $entries->getEntry($i);
			if($entry->getVersions()->getLatest()->getType()!= \Catalog::TYPE_DIR) {
				continue;
			}
			if($entry->hasParentId()) {
				$this->path[$entry->getId()] = $this->path[$entry->getParentId()]."/".$entry->getName();
			}
			if(!$entry->hasParentId()) {
				$this->path[$entry->getId()] = "/".$entry->getName();
			}
			echo "recurse with GET CATALOG ".$entry->getId()." (".$this->path[$entry->getId()].")".PHP_EOL;
			$protocol->sendCommand("GET CATALOG ".$entry->getId());
		}
		
		
		for($i=0;$i<$diff->getNew()->getCount();$i++) {
			#pcntl_signal_dispatch();
			$file = $diff->getNew()->getEntry($i);
			// Skip files for now.
			if($file->getType()!= \Catalog::TYPE_DIR) {
				continue;
			}
			echo "Creating ".$file->getPath().PHP_EOL;
			$protocol->sendCommand("CREATE FILE ".$parentId, $this);
			echo "Sending serialized file".PHP_EOL;
			$protocol->sendSerialize($file, $this);
			/*
			if($file->getType()== \Catalog::TYPE_FILE) {
				echo "Sending ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendFile($file);
					$this->transferred += $file->getSize();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}
			 * 
			 */
			#$entry = $protocol->getUnserializePHP();
			
			#$entry = $this->catalog->newEntry($file, $parent);
			#$entries->addEntry($entry);
			#if($entry->getVersions()->getLatest()->getType()==Catalog::TYPE_FILE) {
			#	$this->storage->store($entry->getVersions()->getLatest(), $this->partition, $file);
			#}
			
			
		}
		#$this->currentEntries = NULL;
		#if($parentId === 0) {
		#	#echo "Ende erreicht.";
		#	$this->quit = TRUE;
		#	$protocol->sendCommand("send ok");
		#}
	}
	
	private function onCatalogEntry(\Net\ProtocolReactive $protocol, \CatalogEntry $entry) {
		if(!$entry->hasParentId()) {
			echo "entry added by the server, /".$entry->getName().PHP_EOL;
			$this->path[$entry->getId()] = "/".$entry->getName();
		} else {
			echo "entry added by the server, ".$this->path[$entry->getParentId()]."/".$entry->getName().PHP_EOL;
			$this->path[$entry->getId()] = $this->path[$entry->getParentId()]."/".$entry->getName();
		}
		echo "Calling read directory with ".$this->path[$entry->getId()].PHP_EOL;
		
		echo "recurse with GET CATALOG ".$entry->getId()." (".$this->path[$entry->getId()].")".PHP_EOL;
		$protocol->sendCommand("GET CATALOG ".$entry->getId());
		$protocol->expect(\Net\ProtocolReactive::SERIALIZED_PHP);
		#$this->currentEntries->addEntry($entry);
	}
	
	private function readDirectory(string $path): \Files {
		$files = new \Files();
		foreach(glob($path."/{,.}*", GLOB_BRACE) as $value) {
			if(in_array(basename($value), array(".", ".."))) {
				continue;
			}
			
			if(is_link($value)) {
				continue;
			}
			#if(in_array($value, $this->exclude)) {
			#	continue;
			#}
			$file = new \File($value);
			if(is_dir($value) and ($this->inex->isValid($value) or $this->inex->transitOnly($value))) {
				$this->processed++;
				if($this->processed%5000==0) {
					echo "Processed ".$this->processed." files.".PHP_EOL;
				}
				$file = new \File($value);
				$files->addEntry($file);
				continue;
			}
			if(is_file($value) and $this->inex->isValid($file->getPath())) {
				$this->processed++;
				if($this->processed%5000==0) {
					echo "Processed ".$this->processed." files.".PHP_EOL;
				}
				$files->addEntry($file);
				continue;
			}
		}
	return $files;
	}
	
	public function onSent(\Net\ProtocolReactive $protocol) {
		#if($this->quit == TRUE) {
		#	exit();
		#}
		
	}

}
