<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.2
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\exceptions;

use Exception;

class RenderingException extends \Exception {
	private $tagname;

    /**
     * RenderingException constructor.
     * @param string $tagname
     * @param string $filename
     * @param int $line
     * @param string $message
     * @param Exception|null $previous
     */
	public function __construct($tagname, $filename, $line, $message, Exception $previous = null) {
		parent::__construct($message);
		$this->tagname = $tagname;
		$this->file = $filename;
		$this->line = $line;
	}
	public function getTagName() {
		return $this->tagname;
	}
}
