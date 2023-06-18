<?php
namespace Net;
/**
 * TransferListener, which is to be supplied to Protocol::getRaw.
 * Having a handle only is sufficient for just files, but more complex storage
 * methods have to be imagined.
 */

interface TransferListener {
	/**
	 * Start transfer with filesize. Filesize is not supposed to change during
	 * transfer.
	 * @param int $size
	 */
	function onStart(int $size);
	/**
	 * Get data from transfer
	 * @param string $data
	 */
	function onData(string $data);
	/**
	 * Transfer has been cancelled (usually recoverable)
	 */
	function onCancel();
	/**
	 * Transfer has failed (recoverable depending on circumstances)
	 */
	function onFail();
	/**
	 * Transfer has finished as planned.
	 */
	function onEnd();
}