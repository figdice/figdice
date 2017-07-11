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

namespace figdice\classes\lexer;

use figdice\classes\Context;

abstract class Token {
	public function __construct() {
	}

    /**
     * @param Context $context
     * @return mixed
     */
	public abstract function evaluate(Context $context);
}
