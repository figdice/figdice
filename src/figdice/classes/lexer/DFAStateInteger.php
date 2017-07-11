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

use figdice\exceptions\LexerUnexpectedCharException;

class DFAStateInteger extends DFAStateNumeric {
	/**
	 * @param Lexer $lexer
	 * @param string $char
	 * @throws LexerUnexpectedCharException
	 */
	public function input(Lexer $lexer, $char) {
		if(self::isDigit($char)) {
			if($this->closed) {
							$this->throwError($lexer, $char);
			} else {
				$this->buffer .= $char;
			}
		} else if($char == '*') {
		    $this->pushStar($lexer);
		} else if($char == ')') {
			$this->pushRParen($lexer);
		} else if($char == ',') {
			$lexer->pushOperand(new TokenLiteral($this->buffer));
			$lexer->incrementLastFunctionArity();
		} else if(self::isBlank($char)) {
			$this->closed = true;
		} else if( ($char == '+') || ($char == '-') ) {
			$this->pushPlusMinus($lexer, $char);
		} else if ( ($char == '=') || ($char == '!') ) {
		    $this->pushEqualsExclam($lexer, $char);
		} else if($char == '.')
		{
			$lexer->setStateDecimal($this->buffer . $char);
		} else if(self::isAlpha($char)) {
		    $this->pushAlpha($lexer, $char);
		}
		//Closing a subpath
		else if($char == ']') {
			$lexer->pushOperand(new TokenLiteral($this->buffer));
			$lexer->closeSquareBracket();
		} else
		{
			$this->throwError($lexer, $char);
		}
	}
}
