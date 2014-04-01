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

namespace figdice\classes\lexer;

class DFAStateDotdot extends DFAState {
	public function __construct() {
		parent::__construct();
	}

		//Valid inputs are:
		// - blank
		// - closing parenthesis
		// - closing square bracket
		// - slash
		// end of input.

	/**
	 * @param Lexer $lexer
	 * @param char $char
	 */
	public function input(Lexer $lexer, $char) {
		if($char == '/') {
			$lexer->pushPath(new PathElementParent());
		}
		else {
			$this->throwError($lexer, $char);
		}
	}
}
