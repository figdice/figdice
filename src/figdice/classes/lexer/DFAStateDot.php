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

class DFAStateDot extends DFAState {
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	public function input(Lexer $lexer, $char) {
		//Valid inputs in this state are:
		// - Slash (/) means that we are talking about the Path Element: Dot,
		// - any digit, means that we are talking about a Decimal,
		// - dot, means that we are talking about the Path Element: Parent.
		// - comma, means that the current argument of a function is the Current path.
        // - closing paren, means that this dot is the last arg in a func call
        // - blank, means that we close the path and it's a dot.
		//TODO: and also closing square bracket.

		if($char == '/') {
			$lexer->pushPath(new PathElementCurrent());
		} else if(self::isDigit($char)) {
			$lexer->setStateDecimal('0.'.$char);
		} else if($char == '.') {
			$lexer->setStateDotdot();
		} else if($char == ',') {
			$lexer->pushOperand(new TokenPath(new PathElementCurrent()));
			$lexer->incrementLastFunctionArity();
		} else if($char == ')') {
			$lexer->pushOperand(new TokenPath(new PathElementCurrent()));
			$lexer->closeParenthesis();
		} else if(self::isBlank($char)) {
			$lexer->pushPath(new PathElementCurrent());
			$lexer->setStateClosedExpression();
		} else {
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
