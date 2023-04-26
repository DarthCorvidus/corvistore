#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
$databasePath = dirname($_SERVER["SCRIPT_FILENAME"])."/crow-protect.sqlite";
$shared = new Shared();
$shared->useSQLite($databasePath);
$adm = new CPAdm($shared->getEPDO());
$adm->run();