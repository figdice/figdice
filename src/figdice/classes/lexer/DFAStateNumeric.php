<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2014, Gabriel Zerbib.
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

abstract class DFAStateNumeric extends DFAState {
	/**
	 * @param Lexer $lexer
	 */
	public function endOfInput($lexer) {
		$lexer->pushOperand(new TokenLiteral($this->buffer));		
	}

	/**
	 * @param Lexer $lexer
	 * @param char $char
	 */
	protected function pushEqualsExclam(Lexer $lexer, $char) {
    	$lexer->pushOperand(new TokenLiteral($this->buffer));
	    $lexer->setStateComparison($char);
	}
	
	
	/**
	 * @param Lexer $lexer
	 * @param char $char
	 */
	protected function pushPlusMinus(Lexer $lexer, $char) {
	    $lexer->pushOperand(new TokenLiteral($this->buffer));
	    $lexer->pushOperator(new TokenPlusMinus($char));
	}
	/**
	 * @param Lexer $lexer
	 * @param char $char
	 */
	protected function pushAlpha(Lexer $lexer, $char) {
	    if(! $this->closed)
	        $this->throwError($lexer, $char);
	    else {
	        $lexer->pushOperand(new TokenLiteral($this->buffer));
	        $lexer->setStateSymbol($char);
	    }
	}

	/**
	 * @param Lexer $lexer
	 */
	protected function pushStar(Lexer $lexer) {
	    $lexer->pushOperand(new TokenLiteral($this->buffer));
	    $lexer->pushOperator(new TokenMul());
	}
	
	/**
	 * @param Lexer $lexer
	 */
	protected function pushRParen(Lexer $lexer) {
	    $lexer->pushOperand(new TokenLiteral($this->buffer));
	    $lexer->closeParenthesis();
	}
}
