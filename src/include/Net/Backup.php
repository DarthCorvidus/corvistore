<?php
namespace Net;
class Backup {
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
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	function __construct(Config $config, array $argv) {
		$this->config = $config;
		$this->argv = $argv;
		$this->inex = $config->getInEx();
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_connect($this->socket, '127.0.0.1', 4096);
		socket_write($this->socket, "node desktop02\n");
		#$this->inex = new InEx();
		#$this->inex->addInclude("/boot/");
		#$this->inex->addInclude("/tmp/");
		#$this->inex->addInclude("/var/log/");
		#$this->inex->addInclude("/home/hm/kernel/");
	}
	
	function readData() {
		$length = \IntVal::uint32LE()->getValue(socket_read($this->socket, 4));
		#echo "Reading ".number_format($length)." bytes of Data.".PHP_EOL;
		if($length<=4096) {
			$data = socket_read($this->socket, $length);
			#echo "Got ".strlen($data).PHP_EOL;
			return $data;
		}
		$rest = $length;
		$data = "";
		while($rest>4096) {
			$data .= socket_read($this->socket, 4096);
			$rest -= 4096;
		}$data .= socket_read($this->socket, $rest);
		#echo "Got ".number_format(strlen($data)).PHP_EOL;
	return $data;
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
			socket_write($this->socket, "GET CATALOG\n");
			$catalogEntries = unserialize($this->readData());
		} else {
			socket_write($this->socket, "GET CATALOG ".$parent->getId()."\n");
			$catalogEntries = unserialize($this->readData());
		}
		
		#$this->pdo->beginTransaction();
		#$catalogEntries = $this->catalog->getEntries($parent);
		$diff = $catalogEntries->getDiff($files);
		// Add new files (did not exist before).
		for($i=0;$i<$diff->getNew()->getCount();$i++) {
			$file = $diff->getNew()->getEntry($i);
			echo "Creating ".$file->getPath().PHP_EOL;
			#$entry = $this->catalog->newEntry($file, $parent);
			#$catalogEntries->addEntry($entry);
			#if($entry->getVersions()->getLatest()->getType()==Catalog::TYPE_FILE) {
			#	$this->storage->store($entry->getVersions()->getLatest(), $this->partition, $file);
			#}
			
			
		}
		// Add changed files.
		for($i=0;$i<$diff->getChanged()->getCount();$i++) {
			$file = $diff->getChanged()->getEntry($i);
			echo "Updating ".$file->getPath().PHP_EOL;
			#$entry = $this->catalog->updateEntry($catalogEntries->getByName($file->getBasename()), $file);
			#$this->storage->store($entry->getVersions()->getLatest(), $this->partition, $file);
		}
		
		// Mark files as deleted.
		for($i=0;$i<$diff->getDeleted()->getCount();$i++) {
			$catalogEntry = $diff->getDeleted()->getEntry($i);
			#echo "Deleting ".$catalogEntry->getName().PHP_EOL;
			#$this->catalog->deleteEntry($catalogEntry);
		}
		#$this->pdo->commit();
		
		$directories = $files->getDirectories();
		for($i=0;$i<$directories->getCount();$i++) {
			$dir = $directories->getEntry($i);
			if($catalogEntries->hasName($dir->getBasename())) {
				$parent = $catalogEntries->getByName($dir->getBasename());
				$this->recurseFiles($dir->getPath(), $depth, $parent);
			}
		}
	}
	
		
	function run() {
		$start = hrtime();
		$this->recurseFiles("/", 0);
		socket_write($this->socket, "QUIT\n");
		$end = hrtime();
		$elapsed = $end[0]-$start[0];
		echo "Processed:    ".number_format($this->processed).PHP_EOL;
		echo "Directories:  ".$this->directories.PHP_EOL;
		echo "Files:        ".$this->files.PHP_EOL;
		echo "Transferred:  ".number_format($this->transferred, 0).PHP_EOL;
		/*
		$stored = $this->pdo->result("select sum(dvs_size) from d_catalog JOIN d_version USING (dc_id) where dnd_id = ? AND dvs_stored = ?", array($this->node->getId(), 1));
		echo "Occupancy:    ".number_format($stored, 0).PHP_EOL;
		$timeconvert = new ConvertTime(ConvertTime::SECONDS, ConvertTime::HMS);
		echo "Elapsed time: ".$timeconvert->convert($elapsed).PHP_EOL;
		*/
	}
}
