<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Administrative Console for Crow Protect
 *
 * @author Claus-Christoph Küthe
 */
class CPAdm {
	private $pdo;
	private $shared;
	private $argv;
	function __construct(EPDO $pdo, array $argv) {
		$this->pdo = $pdo;
		$this->argv = $argv;
	}

	function printWelcome() {
		echo "Crow Protect version less than pre-alpha".PHP_EOL;
		echo "(c) Claus-Christoph Küthe 2023".PHP_EOL;
	}
	
	function getCommand($input = ""): CommandParser {
		while($input=="") {
			#echo "cpmad> ";
			$input = readline("cpadm> ");
			readline_add_history(trim($input));
			#$input = trim(fgets(STDIN));
		}
	return new CommandParser($input);
	}
	
	function handleCommand(CommandParser $command) {
		if($command->getCommand()=="query") {
			$query = new QueryHandler($this->pdo, $command);
			$query->run();
			return;
		}
		if($command->getCommand()=="define") {
			$query = new DefineHandler($this->pdo, $command);
			$query->run();
			return;
		}
		if($command->getCommand()=="update") {
			$query = new UpdateHandler($this->pdo, $command);
			$query->run();
			return;
		}
		if($command->getCommand()=="md5") {
			if($command->getObject()=="") {
				return;
			}
			$storage = Storage::fromName($this->pdo, $command->getObject());
			$stmt = $this->pdo->prepare("select * from n_version2basic where dst_id = ?");
			$stmt->execute(array($storage->getId()));
			foreach($stmt as $key => $value) {
				$path = $storage->getPathForIdFile($value["nvb_id"]);
				echo $path.PHP_EOL;
				echo md5_file($path).PHP_EOL;
			}
		return;
		}
		if($command->getCommand()=="backup") {
			if($command->getObject()!="partition") {
				return;
			}
			$model = new CPModelBackup($this->pdo);
			$command->import($model);
			$sourcePartition = Partition::fromName($this->pdo, $command->getPositional(0));
			$targetPartition = $sourcePartition->getCopyPartition();
			$sourceStorage = Storage::fromId($this->pdo, $sourcePartition->getStorageId());
			$targetStorage = Storage::fromId($this->pdo, $targetPartition->getStorageId());
			$versionsSource = $sourceStorage->getStoredVersions($sourcePartition);
			$versionsTarget = $targetStorage->getStoredVersions($targetPartition);
			$diff = array_diff($versionsSource, $versionsTarget);
			$max = count($diff);
			$i=0;
			$this->pdo->beginTransaction();
			foreach($diff as $key => $value) {
				if($i%100==0) {
					$this->pdo->commit();
					$this->pdo->beginTransaction();
				}
				if($i%1000==0) {
					echo "Copied ".$i." of ".$max.PHP_EOL;
					
				}
				$sourceHandle = $sourceStorage->getReadHandle($sourcePartition, $value);
				$targetStorage->storeFromHandle($targetPartition, $value, $sourceHandle);
				$i++;
			}
			$this->pdo->commit();
		return;
		}
		throw new InvalidArgumentException("Invalid command '".$command->getCommand()."'.");
	}
	
	function run() {
		$this->printWelcome();
		if(isset($this->argv[1])) {
			$command = new CommandParser($this->argv[1]);
			$this->handleCommand($command);
			die();
		}
		while(true) {
			$command = $this->getCommand();
			if($command->getCommand()=="quit") {
				return;
			}
			try {
				$this->handleCommand($command);
			} catch(InvalidArgumentException $e) {
				echo $e->getMessage().PHP_EOL;
			} catch (Exception $e) {
				echo "CrowProtect encountered an error. Please open a ticket with CrowProtect Support according to your support plan, describing the circumstances and providing the following message:".PHP_EOL;
				echo $e->getMessage().PHP_EOL;
				echo $e->getTraceAsString().PHP_EOL;
			}
		}
	}
}
