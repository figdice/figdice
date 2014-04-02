<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
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

namespace figdice\classes\lexer;

class DFAStateDot extends DFAState {
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @param Lexer $lexer
	 * @param char $char
	 */
	public function input(Lexer $lexer, $char) {
		//Valid inputs in this state are:
		// - Slash (/) means that we are talking about the Path Element: Dot,
		// - any digit, means that we are talking about a Decimal,
		// - dot, means that we are talking about the Path Element: Parent.
		// - comma, means that the current argument of a function is the Current path.
		//TODO: and also closing parenthesis, closing square bracket, blank.

		if($char == '/') {
			$lexer->pushPath(new PathElementCurrent());
		}
		else if(self::isDigit($char)) {
			$lexer->setStateDecimal('0.'.$char);
		}
		else if($char == '.') {
			$lexer->setStateDotdot();
		}
		else if($char == ',') {
			$lexer->pushOperand(new TokenPath(new PathElementCurrent()));
			$lexer->incrementLastFunctionArity();
		}
		else if($char == ')') {
			$lexer->pushOperand(new TokenPath(new PathElementCurrent()));
			$lexer->closeParenthesis();
		}
		else if(self::isBlank($char)) {
			$lexer->pushPath(new PathElementCurrent());
			$lexer->setStateClosedExpression();
		}
		else {
			$this->throwError($lexer, $char);
		}
	}

	/**
	 * @param Lexer $lexer
	 */
	public function endOfInput($lexer) {
		$lexer->pushPath(new PathElementCurrent());
	}
}
