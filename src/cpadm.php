#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
$adm = new \Admin\Client($argv);
$adm->run();