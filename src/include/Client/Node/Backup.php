<?php
namespace Node;
class Backup implements \SignalHandler {
	private $config;
	private $argv;
	private $inex;
	private $directories;
	private $files;
	private $node;
	private $partition;
	private $storage;
	private $transferred = 0;
	private $catalog;
	private $processed;
	private $socket;
	private $protocol;
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	function __construct(\Net\Protocol $protocol, \Client\Config $config, array $argv) {
		$this->config = $config;
		$this->argv = $argv;
		$this->inex = $config->getInEx();
		$this->protocol = $protocol;
		$handler = \Signal::get();
		$handler->addSignalHandler(SIGINT, $this);
		$handler->addSignalHandler(SIGTERM, $this);
		#$this->inex = new InEx();
		#$this->inex->addInclude("/boot/");
		#$this->inex->addInclude("/tmp/");
		#$this->inex->addInclude("/var/log/");
		#$this->inex->addInclude("/home/hm/kernel/");
	}
	
	function onSignal(int $signal, array $info) {
		if($signal==SIGINT or $signal==SIGTERM) {
			$this->protocol->sendCommand("QUIT");
			echo "Exit after signal.".PHP_EOL;
			$this->displayResult();
			exit();
		}
	}
	
	private function fileowner($filename) {
		$owner = posix_getpwuid(fileowner($filename));
	return $owner["name"];
	}
	
	private function filegroup($filename) {
		$group = posix_getgrgid(filegroup($filename));
	return $group["name"];
	}
	
	private function recurseFiles(string $path, $depth, \CatalogEntry $parent = NULL) {
		$files = new \Files();
		$directories = array();
		$all = array();
		
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
			if(is_dir($value) and ($this->inex->isValid($value) or $this->inex->transitOnly($value))) {
				$this->processed++;
				if($this->processed%5000==0) {
					echo "Processed ".$this->processed." files.".PHP_EOL;
				}
				$file = new \File($value);
				$files->addEntry($file);
				continue;
			}
			if(is_file($value) and $this->inex->isValid($path)) {
				$this->processed++;
				if($this->processed%5000==0) {
					echo "Processed ".$this->processed." files.".PHP_EOL;
				}
				$file = new \File($value);
				$files->addEntry($file);
				continue;
			}
		}
		if($parent == NULL) {
			$this->protocol->sendCommand("GET CATALOG 0");
			$catalogEntries = $this->protocol->getUnserializePHP();
		} else {
			$this->protocol->sendCommand("GET CATALOG ".$parent->getId());
			$catalogEntries = $this->protocol->getUnserializePHP();
		}
		
		#$this->pdo->beginTransaction();
		#$catalogEntries = $this->catalog->getEntries($parent);
		$diff = $catalogEntries->getDiff($files);
		// Add new files (did not exist before).
		for($i=0;$i<$diff->getNew()->getCount();$i++) {
			pcntl_signal_dispatch();
			$file = $diff->getNew()->getEntry($i);
			// Skip files for now.
			#if($file->getType()!= \Catalog::TYPE_DIR) {
			#	continue;
			#}
			echo "Creating ".$file->getPath().PHP_EOL;
			if($parent == NULL) {
				$this->protocol->sendCommand("CREATE FILE 0");
			} else {
				$this->protocol->sendCommand("CREATE FILE ".$parent->getId());
			}
			$this->protocol->sendSerializePHP($file);
			if($file->getType()== \Catalog::TYPE_FILE) {
				echo "Sending ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendFile($file);
					$this->transferred += $file->getSize();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}
			$entry = $this->protocol->getUnserializePHP();
			
			#$entry = $this->catalog->newEntry($file, $parent);
			$catalogEntries->addEntry($entry);
			#if($entry->getVersions()->getLatest()->getType()==Catalog::TYPE_FILE) {
			#	$this->storage->store($entry->getVersions()->getLatest(), $this->partition, $file);
			#}
			
			
		}
		// Add changed files.
		for($i=0;$i<$diff->getChanged()->getCount();$i++) {
			pcntl_signal_dispatch();
			$file = $diff->getChanged()->getEntry($i);
			echo "Updating ".$file->getPath().PHP_EOL;
			$this->protocol->sendCommand("UPDATE FILE");
			$entry = $catalogEntries->getByName($file->getBasename());
			$this->protocol->sendSerializePHP($file);
			$this->protocol->sendSerializePHP($entry);
			if($file->getType()==\Catalog::TYPE_FILE) {
				echo "Sending ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendFile($file);
					$this->transferred += $file->getSize();
				} catch (\Net\UploadException $e) {
					echo "Skipping file ".$file->getPath().": ".$e->getMessage().PHP_EOL;
				}
			}
			#$entry = $this->catalog->updateEntry($catalogEntries->getByName($file->getBasename()), $file);
			#$this->storage->store($entry->getVersions()->getLatest(), $this->partition, $file);
		}
		
		// Mark files as deleted.
		for($i=0;$i<$diff->getDeleted()->getCount();$i++) {
			pcntl_signal_dispatch();
			$catalogEntry = $diff->getDeleted()->getEntry($i);
			echo "Deleting ".$catalogEntry->getName().PHP_EOL;
			$this->protocol->sendCommand("DELETE ENTRY");
			$this->protocol->sendSerializePHP($catalogEntry);
			#$this->catalog->deleteEntry($catalogEntry);
		}
		#$this->pdo->commit();
		
		$directories = $files->getDirectories();
		for($i=0;$i<$directories->getCount();$i++) {
			pcntl_signal_dispatch();
			$dir = $directories->getEntry($i);
			if($catalogEntries->hasName($dir->getBasename())) {
				$parent = $catalogEntries->getByName($dir->getBasename());
				$this->recurseFiles($dir->getPath(), $depth, $parent);
			}
		}
	}
	
	private function displayResult() {
		echo "Processed:    ".number_format($this->processed).PHP_EOL;
		echo "Directories:  ".$this->directories.PHP_EOL;
		echo "Files:        ".$this->files.PHP_EOL;
		echo "Transferred:  ".number_format($this->transferred, 0).PHP_EOL;
	}
		
	function run() {
		#$start = hrtime();
		$this->recurseFiles("/", 0);
		$this->protocol->sendCommand("QUIT");
		$this->displayResult();
		#$end = hrtime();
		#$elapsed = $end[0]-$start[0];
		/*
		$stored = $this->pdo->result("select sum(dvs_size) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ? AND dvs_stored = ?", array($this->node->getId(), 1));
		echo "Occupancy:    ".number_format($stored, 0).PHP_EOL;
		$timeconvert = new ConvertTime(ConvertTime::SECONDS, ConvertTime::HMS);
		echo "Elapsed time: ".$timeconvert->convert($elapsed).PHP_EOL;
		*/
	}
}
