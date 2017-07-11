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

class FileNotFoundException extends \Exception {
	/**
	 * @var string
	 */
	private $filename;

	/**
	 * @param string $message
	 * @param string $filename
	 */
	public function __construct($message, $filename) {
		parent::__construct($message);
		$this->filename = $filename;
	}

	/**
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}
}