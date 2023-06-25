#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
$databasePath = "/var/lib/crow-protect/crow-protect.sqlite";
$shared = new Shared();
$shared->useSQLite($databasePath);
$arg = new ArgvServe($argv);
if($arg->hasRunPath()) {
	try {
		$commands = file($arg->getRunPath());
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
	$adm = new CPServe($shared->getEPDO(), $argv);
	$adm->run();
} catch (RuntimeException $e) {
	echo $e->getMessage().PHP_EOL;
}
