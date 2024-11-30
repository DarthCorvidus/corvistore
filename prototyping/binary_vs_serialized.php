#!/usr/bin/php
<?php
require_once __DIR__."/../vendor/autoload.php";

$file = File::fromPath(__DIR__."/../vendor/autoload.php");
print_r($file);

$binary = random_bytes(1024*1024*10);
$serialize[] = random_bytes(1024);
$serialize[] = random_bytes(1024);
echo strlen(serialize($serialize)).PHP_EOL;
#die();
$run = pow(2, 16);
$serSize = 0;
$binSize = 0;
\plibv4\profiler\Profiler::startTimer("serialize");
for($i=0;$i<$run; $i++) {
	$serialized = serialize($file);
	$serSize += strlen($serialized);
	$unserialized = unserialize($serialized);
}
\plibv4\profiler\Profiler::endTimer("serialize");
echo "Serialize done".PHP_EOL;


\plibv4\profiler\Profiler::startTimer("binary");
for($i=0;$i<$run; $i++) {
	$binary = $file->toBinary();
	$binSize += strlen($binary);
	$cl = File::fromBinary($binary);
}
\plibv4\profiler\Profiler::endTimer("binary");

echo "Serialized Size: ".number_format($serSize).PHP_EOL;
echo "Binary Size:     ".number_format($binSize).PHP_EOL;
echo "Diff:            ".number_format($serSize-$binSize).PHP_EOL;

\plibv4\profiler\Profiler::printTimers();