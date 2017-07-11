<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.4
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

class Function_range implements FigFunction {
    /**
     * This function returns an array of N elements, from 1 to N.
     * It is useful for creating small "for" loops in your views.
     * @param Context $context
     * @param integer $arity
     * @param array $arguments one element: Size of the 1..N array
     * @return array|mixed
     */
	public function evaluate(Context $context, $arity, $arguments) {

		$rangeSize = intval($arguments[0]);

        $result = array();
        for ($i = 1; $i <= $rangeSize; ++ $i) {
            $result []= $i;
        }

        return $result;
    }
}
