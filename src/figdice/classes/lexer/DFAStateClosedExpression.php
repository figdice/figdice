<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes\lexer;

class DFAStateClosedExpression extends DFAState {
	public function __construct() {
		parent::__construct();
		$this->closed = true;
	}

	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	public function input(Lexer $lexer, $char)
	{
		if(self::isBlank($char)) {
		} else if( ($char == '+') || ($char == '-') ) {
			$lexer->pushOperator(new TokenPlusMinus($char));
		} else if($char == '*') {
			$lexer->pushOperator(new TokenMul());
		} else if($char == '!') {
			$lexer->setStateComparison($char);
		} else if($char == '=') {
			$lexer->setStateComparison($char);
		} else if(self::isAlpha($char)) {
			$lexer->setStateSymbol($char);
		} else if($char == ')') {
			$lexer->closeParenthesis();
		} else if($char == ']') {
			$lexer->closeSquareBracket();
		} else if($char == ',') {
			$lexer->incrementLastFunctionArity();			
		} else {
			$this->throwError($lexer, $char);
		}
	}
	/**
	 * @param Lexer $lexer
	 */
	public function endOfInput($lexer) {
		//Deliberately left blank!
	}
}
