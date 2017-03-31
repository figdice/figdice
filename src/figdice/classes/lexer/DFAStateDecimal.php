<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.2
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

class DFAStateDecimal extends DFAStateNumeric {
	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	public function input(Lexer $lexer, $char) {
		if(self::isDigit($char)) {
			$this->buffer .= $char;
		}
		else if(self::isBlank($char)) {
			$this->closed = true;
		}
		else if($char == ')') {
		    $this->pushRParen($lexer);
		}
		else if($char == ',') {
			$lexer->pushOperand(new TokenLiteral($this->buffer));
			$lexer->incrementLastFunctionArity();
		}
		else if(self::isAlpha($char)) {
		    $this->pushAlpha($lexer, $char);
		}
		else if('*' == $char) {
			$this->pushStar($lexer);
		}
		else if (('+' == $char) || ('-' == $char) ) {
		    $this->pushPlusMinus($lexer, $char);
		}
		else if ( ($char == '=') || ($char == '!') ) {
		    $this->pushEqualsExclam($lexer, $char);
		}
		else {
			$this->throwError($lexer, $char);
		}
	}
}
