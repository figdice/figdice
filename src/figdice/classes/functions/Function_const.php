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

namespace figdice\classes\functions;

use figdice\classes\Context;
use figdice\FigFunction;

class Function_const implements FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments one element: name of global constant, or class constant (myClass::myConst)
     * @return mixed|null
     */
    public function evaluate(Context $context, $arity, $arguments) {
		$constantName = trim($arguments[0]);
		
		if(preg_match('#([^:]+)::#', $constantName, $matches)) {
			$className = $matches[1];
			if(! class_exists($className)) {
				// ("Undefined class: $className in static: $constantName");
				return null;
			}
		}
		
		//Global constant
		if(defined($constantName)) {
			return constant($constantName);
		}
		//Undefined symbol: assume the value is same as constant name
		else {
			return $constantName;
		}
	}
}
