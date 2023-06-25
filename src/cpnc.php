#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
try {
	$backup = new Node\Client($argv);
	$backup->run();
} catch (Exception $e) {
	echo $e->getMessage().PHP_EOL;
}
