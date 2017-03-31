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

namespace figdice\classes\lexer;

class DFAStateString extends DFAState {

	private $escaping = false;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	public function input(Lexer $lexer, $char) {

		if($this->escaping) {
			$this->buffer .= $char;
			$this->escaping = false;
		}
		else if($char == "'") {
			$lexer->pushOperand(new TokenLiteral($this->buffer));
			$this->closed = true;
		}
		else if($char == '\\') {
			$this->escaping = true;
		}
		else if($this->closed) {
			if($char == ',') {
				$lexer->incrementLastFunctionArity();
				$lexer->setStateEmpty();
			}
			else if($char == ')') {
				$lexer->closeParenthesis();
			}
			else if(self::isBlank($char)) {
			}
			else if( ($char == '+') || ($char == '-') ) {
				$lexer->pushOperator(new TokenPlusMinus($char));
			}
			else if ( ($char == '=') || ($char == '!') ) {
				$lexer->setStateComparison($char);
			}
			else if($char == '*') {
				$lexer->pushOperator(new TokenMul());
			}
			else if($char == ']') {
				$lexer->closeSquareBracket();
			}
			else {
				$this->throwError($lexer, $char);
			}
		}
		else {
			$this->buffer .= $char;
		}
	}

	/**
	 * @param Lexer $lexer
	 */
	public function endOfInput($lexer)
	{
		if(! $this->closed)
			$this->throwErrorWithMessage($lexer, 'Unterminated string');
	}
}
