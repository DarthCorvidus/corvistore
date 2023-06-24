#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
$databasePath = "/var/lib/crow-protect/crow-protect.sqlite";
$shared = new Shared();
$shared->useSQLite($databasePath);
$adm = new \Admin\Client($argv);
$adm->run();