<?php
namespace Storage;
/**
 * A StorageJob is just a way to add a file to TaskSingleStorage in one go and
 * put it onto TaskSingleStorage's queue.
 */
class StorageJob {
	public \File $file;
	public \Catalog $catalog;
	public string $filedata;
	public \Partition $partition;
	public \Node $node;
	function __construct(\File $file, \Catalog $catalog, \Partition $partition, \Node $node, string $filedata) {
		$this->file = $file;
		$this->catalog = $catalog;
		$this->filedata = $filedata;
		$this->partition = $partition;
		$this->node = $node;
	}
}
