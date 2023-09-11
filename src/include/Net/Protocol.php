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
	
	static function determineControlBlock(string $block): int {
		$type1 = ord($block[0]);
		$type2 = ord($block[strlen($block)-1]);
		if($type1!==$type2) {
			throw new \RuntimeException("malformed control block, ".$type1." does not equal ".$type2);
		}
	return $type1;
	}
	
	/**
	 * This ought to be much faster than, say, ($i/1024)*1024, but 1024 has to
	 * be given as bit length, ie 2^10 = 1024. Having to convert 1024 into 10
	 * first would kind of defeat the value of this method.
	 * @param int $value
	 * @param int $bit
	 * @return type
	 */
	static function ceilBlock(int $value, int $bit) {
	// Bitshift by 2^$bit
	return ((($value-1) >> $bit)+1) << $bit;
	}
}
