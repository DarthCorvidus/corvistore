#!/usr/bin/env php
<?php
require_once __DIR__."/../../vendor/autoload.php";
foreach(glob(__DIR__."/include/*.php") as $value) {
	require_once $value;
}
$directory1 = new Regression\Directory($argv[1]);
$directory2 = new Regression\Directory($argv[2]);

$ret1 = $directory1->checkEqual($directory2);
$ret2 = $directory2->checkEqual($directory1);

if($ret1 === TRUE and $ret2 === TRUE) {
	exit(0);
} else {
	exit(1);
}