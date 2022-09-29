<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.3
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\exceptions;

class LexerSyntaxErrorException extends \Exception {
    /**
     * LexerSyntaxErrorException constructor.
     *
     * @param string $message
     * @param string $file
     * @param int $line
     */
    public function __construct($message, $file, $line) {
		parent::__construct($message, 0);
		$this->file = $file ?? '';
		$this->line = $line ?? 0;
	}
}
