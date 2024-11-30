<?php
namespace Node;
interface RecurseDirectoryObserver {
	function onFile(string $path);
}