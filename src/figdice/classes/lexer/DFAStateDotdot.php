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
	 * @param string $char
	 */
	public function input(Lexer $lexer, $char) {
		if($char == '/') {
			$lexer->pushPath(new PathElementParent());
		} else {
			$this->throwError($lexer, $char);
		}
	}
}
