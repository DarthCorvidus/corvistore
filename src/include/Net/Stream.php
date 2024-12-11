<?php
namespace Net;
interface Stream {
	function read(int $amount): string;
	function write(string $data): int;
	function close(): void;
}
