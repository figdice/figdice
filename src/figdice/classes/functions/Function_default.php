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

namespace figdice\classes\functions;

use figdice\classes\Context;
use figdice\FigFunction;

class Function_default implements FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return mixed
     */
    public function evaluate(Context $context, $arity, $arguments) {
		return ($arguments[0] ? $arguments[0] : $arguments[1]);
	}
}