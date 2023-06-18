<?php
namespace Net;
/**
 * To be thrown sender side if an upload fails, because...<br />
 * - a file changes during upload
 * - a file vanishes during upload
 */
class UploadException extends \RuntimeException {
	
}