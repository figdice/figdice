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

class Function_php implements FigFunction {
    /**
     * @param Context $context
     * @param int $arity
     * @param array $arguments
     * @return bool|mixed
     */
    public function evaluate(Context $context, $arity, $arguments) {
		$funcName = array_shift($arguments);
		if(! function_exists($funcName)) {
			// ('Invalid PHP function: ' . $funcName);
			return false;
		}

		return call_user_func_array($funcName, $arguments);
	}
}