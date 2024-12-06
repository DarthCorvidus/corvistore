<?php
namespace Node;
use plibv4\process\Scheduler;
use plibv4\process\Timeshare;
use plibv4\process\TimeshareObserver;
class Backup implements \SignalHandler {
	private \Client\Config $config;
	private \ArgvBackup $argv;
	private \InEx $inex;
	private int $directories = 0;
	private int $files = 0;
	private int $transferred = 0;
	private int $processed  = 0;
	private mixed $socket;
	private \Net\ProtocolAsync $protocol;
	private Timeshare $timeshare;
	private ProtocolNode $protocolNodeListener;
	const TYPE_DELETED = 0;
	const TYPE_DIR = 1;
	const TYPE_FILE = 2;
	function __construct(mixed $socket, \Client\Config $config, array $argv) {
		$this->config = $config;
		$this->argv = new \ArgvBackup($argv);
		$this->inex = $this->getInEx();
		$this->protocolNodeListener = new ProtocolNode();
		$this->protocol = new \Net\ProtocolAsync($this->protocolNodeListener);
		$this->protocolNodeListener->setProtocol($this->protocol);
		/*
		 * Create path from root to starting point if necessary, ie when user
		 * calls cpnc backup /home/user/files, create /home/ and /home/user/.
		 */
		$this->createHierarchy($socket);
		
		$client = new \Net\AsyncStream($socket);
		$client->setProtocol($this->protocol);
		$this->timeshare = new Timeshare();
		$this->protocolNodeListener->setScheduler($this->timeshare);
		$this->timeshare->addTask($client);
		$this->timeshare->addTask(new RecurseDirectory($this->argv->getBackupPath(), $this->inex, $this->protocolNodeListener));
	}
	/**
	 * Determines/creates Include/Exclude-List. Command line parameters have
	 * precedence.
	 * @return \InEx
	 */
	private function getInEx(): \InEx {
		if(!$this->argv->hasExcludeList() && $this->argv->hasIncludeList()) {
			$inex = $this->config->getInEx();
		return $inex;
		}
		$inex = new \InEx();
		if($this->argv->hasExcludeList()) {
			$excludeList = $this->argv->getExcludeList();
			$excludes = file($excludeList);
			foreach($excludes as $value) {
				$inex->addExclude(trim($value));
			}
		}

		if($this->argv->hasIncludeList()) {
			$includeList = $this->argv->getIncludeList();
			$includes = file($includeList);
			foreach($includes as $value) {
				$inex->addInclude(trim($value));
			}
		}
	return $inex;
	}
	
	function onSignal(int $signal, array $info): void {
		if($signal==SIGINT or $signal==SIGTERM) {
			$this->protocol->sendCommand("DONE");
			$this->protocol->sendOK();
			$this->protocol->sendCommand("QUIT");
			echo "Exit after signal.".PHP_EOL;
			$this->displayResult();
			exit();
		}
	}
	
	/**
	 * When using cpnc backup with a path below the root directory, path entries
	 * leading to the point at which backing up starts nevertheless need to be
	 * created, otherwise the client can't restore if 'cpnc restore' is used.
	 * As this is older code, it still uses the synchronized code, but the
	 * performance penalty will be negligible in most if not all use cases.
	 * 
	 * @param mixed $socket
	 */
	private function createHierarchy(mixed $socket): void {
		$protocol = new \Net\ProtocolSync(new \Net\StreamClient($socket));
		$exp = explode("/", realpath($this->argv->getBackupPath()));
		$path = array();
		$prev = "";
		foreach($exp as $value) {
			if($value==="") {
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
			$protocol->sendCommand("GET CATALOG ".dirname($dir));
			$entries = $protocol->getSerialized();
			if(!$entries->hasName(basename($dir))) {
				echo "Creating ".$dir.PHP_EOL;
				$file = \File::fromPath($dir);
				$file->setAction(\File::CREATE);
				$this->protocol->sendCommand("CREATE FILE ".$dir);
				$this->protocol->sendSerialize($file);
			}
		}
	}
	
	private function displayResult(): void {
		echo "Processed:    ".number_format($this->processed).PHP_EOL;
		echo "Directories:  ".$this->directories.PHP_EOL;
		echo "Files:        ".$this->files.PHP_EOL;
		echo "Transferred:  ".number_format($this->transferred, 0).PHP_EOL;
	}
		
	function run(): void {
		echo "---Iterator---".PHP_EOL;
		$start = microtime(true);
		$this->timeshare->run();
		echo "Time: ".(microtime(true)-$start).PHP_EOL;
		$table = new \TerminalTable($this->protocolNodeListener->getBackupStat());
		$table->printTable();
	}
}
