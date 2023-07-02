#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
$databasePath = "/var/lib/crow-protect/crow-protect.sqlite";
$shared = new Shared();
$shared->useSQLite($databasePath);
$arg = new ArgvServe($argv);

if($arg->hasRun()) {
	try {
		echo $arg->getRun().PHP_EOL;
		$command = new CommandParser($arg->getRun());
		$handler = new CommandHandler($shared->getEPDO(), $command);
		echo $handler->execute();
		echo PHP_EOL;
	} catch (Exception $e) {
		echo $e->getMessage().PHP_EOL;
	}
	exit();
}

if($arg->hasRunFile()) {
	try {
		$commands = file($arg->getRunFile());
		foreach($commands as $cmd) {
			echo $cmd;
			$command = new CommandParser(trim($cmd));
			$handler = new CommandHandler($shared->getEPDO(), $command);
			echo $handler->execute();
			echo PHP_EOL;
		}
	} catch (Exception $e) {
		echo $e->getMessage().PHP_EOL;
	}
	exit();
}

try {
	$adm = new Server($shared->getEPDO(), $argv);
	$adm->run();
} catch (RuntimeException $e) {
	echo $e->getMessage().PHP_EOL;
}
