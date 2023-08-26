<?php
namespace Net;
interface Stream {
	function read(int $amount): string;
	function write(string $string);
	function close();
}
