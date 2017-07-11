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

class XMLParsingException extends \Exception {
    /**
     * XMLParsingException constructor.
     * @param string $message
     * @param int $line
     */
    public function __construct($message, $line) {
		parent::__construct($message);
		$this->line = $line;
	}
}
