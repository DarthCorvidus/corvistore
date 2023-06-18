<?php
namespace Net;
/**
 * Exception that is to be thrown if Protocol::getOK() receives CANCEL instead,
 * which is supposed to be recoverable (client is supposed to send cancel if it
 * thinks that a transfer should be aborted).
 */
class CancelException extends \RuntimeException {
	
}