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

class Function_format_number implements FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return string
     */
	public function evaluate(Context $context, $arity, $arguments) {
		$number = $arguments[0];
		if(! is_numeric($number)) {
			return '';
		}

		if(isset($arguments[1])) {
			$decimals = $arguments[1];
		}
		else {
			$decimals = 2;
		}

		if(isset($arguments[2])) {
			$dec_point = $arguments[2];
		}
		else {
			$dec_point = ',';
		}

		if(isset($arguments[3])) {
			$thousands_sep = $arguments[3];
		}
		else {
			$thousands_sep = ' ';
		}

		return number_format(doubleval($number), $decimals, $dec_point, $thousands_sep);
	}
}