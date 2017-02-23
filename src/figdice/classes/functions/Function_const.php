<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.3
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 * FigDice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * FigDice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FigDice.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace figdice\classes\functions;

use figdice\classes\Context;
use \figdice\FigFunction;
use \figdice\LoggerFactory;

class Function_const implements FigFunction {
	public function __construct() {
	}

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
				$logger = LoggerFactory::getLogger(__CLASS__);
				$logger->warning("Undefined class: $className in static: $constantName");
				return null;
			}
		}
		
		//Global constant
		if(defined($constantName)) {
			return constant($constantName);
		}
		//Undefined symbol: error.
		else {
			$logger = LoggerFactory::getLogger(__CLASS__);
			$logger->warning("Undefined constant: $constantName");
			return null;
		}
	}
}
