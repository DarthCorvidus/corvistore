<?php
namespace Storage;
interface Strategy {
	function storeSingle(int $id, string $filedata): void;
}