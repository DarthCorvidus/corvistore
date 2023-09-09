<?php
namespace Net;
abstract class Protocol {
	const OK = 1;
	const MESSAGE = 2;
	const COMMAND = 3;
	const SERIALIZED_PHP = 4;
	const FILE = 5;
	const ERROR = 255;
	const FILE_OK = 1;
	const FILE_RESEND = 2;
	const FILE_CANCEL = 3;
	static function padRandom(string $string, $padlength): string {
		$len = strlen($string);
		if($len==$padlength) {
			return $string;
		}
		if($len<$padlength) {
			return $string.random_bytes($padlength-$len);
		}
		/*
		 * Throw exception here, as a longer pad length should not happen in the
		 * context of ProtocolBase.
		 */
		if($len>$padlength) {
			throw new \RuntimeException("padlength ".$padlength." shorter than strlen ".$len);
		}
	}
	
	static function getControlBlock(int $type, int $length): string {
		return chr($type).random_bytes($length-2).chr($type);
	}
}
