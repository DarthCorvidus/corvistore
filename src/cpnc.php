#!/usr/bin/env php
<?php
require_once __DIR__."/../vendor/autoload.php";
$backup = new Net\Client($argv);
$backup->run();