#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
try {
	$adm = new CPServe($argv);
	$adm->run();
} catch (RuntimeException $e) {
	echo $e->getMessage().PHP_EOL;
}
