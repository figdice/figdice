<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
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
