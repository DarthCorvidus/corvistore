<?php
namespace Storage;
/**
 * A StorageJob is just a way to add a file to TaskSingleStorage in one go and
 * put it onto TaskSingleStorage's queue.
 */
class StorageJob {
	public \File $file;
	public \VersionEntry $versionEntry;
	public string $filedata;
	function __construct(\File $file, \VersionEntry $versionEntry, string $filedata) {
		$this->file = $file;
		$this->versionEntry = $versionEntry;
		$this->filedata = $filedata;
	}
}
