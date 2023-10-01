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
	function __construct(\Net\ProtocolSync $protocol, \Client\Config $config, array $argv) {
		$this->config = $config;
		$this->argv = new \ArgvBackup($argv);
		$this->inex = $config->getInEx();
		$this->protocol = $protocol;
		/*
		$handler = \Signal::get();
		$handler->addSignalHandler(SIGINT, $this);
		$handler->addSignalHandler(SIGTERM, $this);
		 * 
		 */
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
	
	private function recurseFiles(string $path) {
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
				$file = \File::fromPath($value);
				$files->addEntry($file);
				continue;
			}
			if(is_file($value) and $this->inex->isValid($path)) {
				$this->processed++;
				if($this->processed%5000==0) {
					echo "Processed ".$this->processed." files.".PHP_EOL;
				}
				$file = \File::fromPath($value);
				$files->addEntry($file);
				continue;
			}
		}
		$this->protocol->sendCommand("GET CATALOG ".$path);
		$catalogEntries = $this->protocol->getSerialized();
		/*
		if($parent == NULL) {
			$this->protocol->sendCommand("GET CATALOG 0");
			$catalogEntries = $this->protocol->getUnserializePHP();
		} else {
			$this->protocol->sendCommand("GET CATALOG ".$parent->getId());
			$catalogEntries = $this->protocol->getUnserializePHP();
		}
		*/
		#$this->pdo->beginTransaction();
		#$catalogEntries = $this->catalog->getEntries($parent);
		$diff = $catalogEntries->getDiff($files);
		// Add new files (did not exist before).
		for($i=0;$i<$diff->getNew()->getCount();$i++) {
			#pcntl_signal_dispatch();
			$file = $diff->getNew()->getEntry($i);
			// Skip files for now.
			#if($file->getType()!= \Catalog::TYPE_DIR) {
			#	continue;
			#}
			echo "Creating ".$file->getPath().PHP_EOL;
			#$this->protocol->sendCommand("CREATE FILE ".$file->getPath());
			$file->setAction(\File::CREATE);
			$this->protocol->sendSerialize($file);
			if($file->getType()== \Catalog::TYPE_FILE) {
				echo "Sending ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendStream(new \Net\FileSender($file));
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
		// Add changed files.
		for($i=0;$i<$diff->getChanged()->getCount();$i++) {
			#pcntl_signal_dispatch();
			$file = $diff->getChanged()->getEntry($i);
			$entry = $catalogEntries->getByName($file->getBasename());
			echo "Updating ".$file->getPath().PHP_EOL;
			$file->setAction(\File::UPDATE);
			$this->protocol->sendCommand("UPDATE FILE ".$entry->getId());
			$this->protocol->sendSerialize($file);
			#$this->protocol->sendSerializePHP($entry);
			if($file->getType()==\Catalog::TYPE_FILE) {
				echo "Sending ".$file->getPath().PHP_EOL;
				try {
					$this->protocol->sendStream(new \Net\FileSender($file));
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
			#pcntl_signal_dispatch();
			$catalogEntry = $diff->getDeleted()->getEntry($i);
			echo "Deleting ".$catalogEntry->getDirname()."/".$catalogEntry->getName().PHP_EOL;
			$this->protocol->sendCommand("DELETE ENTRY ".$catalogEntry->getId());
		}
		#$this->pdo->commit();
		
		$directories = $files->getDirectories();
		for($i=0;$i<$directories->getCount();$i++) {
			#pcntl_signal_dispatch();
			$dir = $directories->getEntry($i);
			$this->recurseFiles($dir->getPath());
		}
	}
	
	private function createHierarchy() {
		$exp = explode("/", $this->argv->getBackupPath());
		$path = array();
		$prev = "";
		foreach($exp as $key => $value) {
			if($value===NULL or $value==="") {
				$path[] = $value;
				$check = "/";
			} else {
				$path[] = $value;
				$check = implode("/", $path);
			}
			if($check=="/") {
				continue;
			}
			$dir = implode("/", $path);
			/**
			 * Getting the whole directory here is somewhat wasteful, but the
			 * alternatives aren't that much better:
			 * - new command that checks if an directory already exists
			 * - new command that checks if a directory already exists before
			 *   creating it
			 * - altering CREATE FILE to check before creating (more secure, but
			 *   consumes more time)
			 */
			$this->protocol->sendCommand("GET CATALOG ".dirname($dir));
			$entries = $this->protocol->getSerialized();
			if(!$entries->hasName(basename($dir))) {
				echo "Creating ".$dir.PHP_EOL;
				$file = \File::fromPath($dir);
				$file->setAction(\File::CREATE);
				$this->protocol->sendCommand("CREATE FILE ".$dir);
				$this->protocol->sendSerialize($file);
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
		#\plibv4\profiler\Profiler::startTimer("recurse");
		if($this->argv->getBackupPath()!="/") {
			$this->createHierarchy();
			echo $this->argv->getBackupPath().PHP_EOL;
			$this->recurseFiles($this->argv->getBackupPath());
		} else {
			$this->recurseFiles("/");
		}
		echo "Sending quit...".PHP_EOL;
		$this->protocol->sendCommand("QUIT");
		$this->displayResult();
		#\plibv4\profiler\Profiler::endTimer("recurse");
		#\plibv4\profiler\Profiler::printTimers();
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
