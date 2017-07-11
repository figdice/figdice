<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes\lexer;

class DFAStateComparison extends DFAState {
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	public function input(Lexer $lexer, $char)
	{
		if($char == '=')
		{
			$this->buffer .= $char;
		}
		else if(self::isBlank($char))
		{
			$lexer->pushOperator(new TokenComparisonBinop($this->buffer));
		}
		else
		{
			$lexer->pushOperator(new TokenComparisonBinop($this->buffer));
			$lexer->forwardInput($char);
		}
	}
  public function endOfInput($lexer) {
    $this->throwErrorWithMessage($lexer, 'Unexpected end of expression in comparator.');
  }
}
