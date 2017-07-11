<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.3
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
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
		} else if($char == "'") {
			$lexer->pushOperand(new TokenLiteral($this->buffer));
			$this->closed = true;
		} else if($char == '\\') {
			$this->escaping = true;
		} else if($this->closed) {
			if($char == ',') {
				$lexer->incrementLastFunctionArity();
				$lexer->setStateEmpty();
			} else if($char == ')') {
				$lexer->closeParenthesis();
			} else if(self::isBlank($char)) {
			} else if( ($char == '+') || ($char == '-') ) {
				$lexer->pushOperator(new TokenPlusMinus($char));
			} else if ( ($char == '=') || ($char == '!') ) {
				$lexer->setStateComparison($char);
			} else if($char == '*') {
				$lexer->pushOperator(new TokenMul());
			} else if($char == ']') {
				$lexer->closeSquareBracket();
			} else {
				$this->throwError($lexer, $char);
			}
		} else {
			$this->buffer .= $char;
		}
	}

	/**
	 * @param Lexer $lexer
	 */
	public function endOfInput($lexer)
	{
		if(! $this->closed) {
					$this->throwErrorWithMessage($lexer, 'Unterminated string');
		}
	}
}
