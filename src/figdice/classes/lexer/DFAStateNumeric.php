<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.1
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
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
	 * @param string $char
	 */
	protected function pushEqualsExclam(Lexer $lexer, $char) {
    	$lexer->pushOperand(new TokenLiteral($this->buffer));
	    $lexer->setStateComparison($char);
	}
	
	
	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	protected function pushPlusMinus(Lexer $lexer, $char) {
	    $lexer->pushOperand(new TokenLiteral($this->buffer));
	    $lexer->pushOperator(new TokenPlusMinus($char));
	}
	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	protected function pushAlpha(Lexer $lexer, $char) {
	    if(! $this->closed) {
	    	        $this->throwError($lexer, $char);
	    } else {
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
