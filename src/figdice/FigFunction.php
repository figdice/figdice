<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
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

namespace figdice;

use figdice\classes\ViewElementTag;

/**
 * Your FigFunction instances are created at most once per View lifecycle.
 * If the same function is invoked several times in the template, there will
 * still be only one instance of your FigFunction object, and its evaluate
 * method is invoked each time.
 * Therefore, you can maintain instance properties (for calcualation caching
 * purposes for instance, or DB access and so on), but you cannot rely on "fresh"
 * instance in your evalute function.
 */
interface FigFunction {
	/**
	 * @param ViewElementTag $viewElement
	 * @param integer $arity
	 * @param array $arguments
	 */
	function evaluate(ViewElementTag $viewElement, $arity, $arguments);
}