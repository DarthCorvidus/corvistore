<?php
require_once __DIR__."/TestHelper.php";
require_once __DIR__."/../vendor/autoload.php";
foreach(glob(__DIR__."/lib/*.php") as $value) {
	require_once $value;
}