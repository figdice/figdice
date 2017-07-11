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

class Function_GET implements FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return bool|mixed
     */
    public function evaluate(Context $context, $arity, $arguments) {
		if(isset($_GET)) {
			$varName = $arguments[0];
			if(isset($_GET[$varName])) {
				return $_GET[$varName];
			}
		}
		return false;
	}
}