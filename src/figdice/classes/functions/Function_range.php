<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.4
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

use \figdice\FigFunction;
use \figdice\classes\ViewElementTag;
use \figdice\LoggerFactory;

class Function_range implements FigFunction {
	public function __construct() {
	}

	/**
   * This function returns an array of N elements, from 1 to N.
   * It is useful for creating small "for" loops in your views.
	 * @param ViewElement $viewElement
	 * @param integer $arity
	 * @param array $arguments one element: Size of the 1..N array
	 */
	public function evaluate(ViewElementTag $viewElement, $arity, $arguments) {

		$rangeSize = intval($arguments[0]);

    $result = array();
    for ($i = 1; $i <= $rangeSize; ++ $i) {
      $result []= $i;
    }

    return $result;
	}
}
