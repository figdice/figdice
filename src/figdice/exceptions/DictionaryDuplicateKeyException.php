<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\exceptions;

class DictionaryDuplicateKeyException extends \Exception {

	private $filename;
	private $key;

	public function __construct($filename, $key) {
		parent::__construct();
		$this->filename = $filename;
		$this->key = $key;
	}
	/**
	 * The filename of the dictionary where the key was defined more than once.
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}
	/**
	 * The key which was defined more than once in the dictionary file.
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}
}
