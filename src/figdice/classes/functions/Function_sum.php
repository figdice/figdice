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

class Function_sum implements FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return float|mixed
     */
    public function evaluate(Context $context, $arity, $arguments) {
		$collection = $arguments[0];
		$sum = 0;
		if(is_array($collection)) {
			foreach ($collection as $value) {
				$sum += (is_numeric($value) ? $value : 0);
			}
		}
		return doubleval($sum);
	}
}