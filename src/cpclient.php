#!/usr/bin/php
<?php
require_once __DIR__."/../vendor/autoload.php";
$adm = new CPClient($argv);
$adm->run();