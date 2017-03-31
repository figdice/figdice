<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @package FigDice
 */

namespace figdice\classes\functions;

use figdice\classes\Context;
use figdice\FigFunction;

class Function_average implements FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return float|mixed
     */
	public function evaluate(Context $context, $arity, $arguments) {
		$collection = $arguments[0];
		$avg = 0;
		$count = 0;
		if(is_array($collection)) {
			foreach ($collection as $value) {
				if(is_numeric($value)) {
					$avg = (($count * $avg) + $value) / (++ $count); 
				}
			}
		}
		return doubleval($avg);
	}
}