<?php
namespace Net;
/**
 * MismatchException is to be thrown when something unexpected is sent, say, the
 * server sends Protocol::Message, but the client expected Protocol::File. This
 * is usually a programmer's error.
 *
 */
class ProtocolMismatchException extends \RuntimeException {
}
