#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
$databasePath = "/var/lib/crow-protect/crow-protect.sqlite";
$shared = new Shared();
$shared->useSQLite($databasePath);
try {
	$adm = new CPServe($shared->getEPDO(), $argv);
	$adm->run();
} catch (RuntimeException $e) {
	echo $e->getMessage().PHP_EOL;
}
