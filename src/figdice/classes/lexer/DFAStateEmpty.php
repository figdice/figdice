<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.4
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

class DFAStateEmpty extends DFAState {
  public function __construct() {
    parent::__construct();
  }

  /**
   * @param Lexer $lexer
   * @param string $char
   */
  public function input(Lexer $lexer, $char)
  {
    if($char == "'")
    {
      $lexer->setStateString();
    }
    else if(self::isAlpha($char))
    {
      $lexer->setStateSymbol($char);
    }
    else if( ($char == '-') || ($char == '+') )
    {
      $lexer->pushOperator(new TokenUnarySign($char));
    }
    else if(self::isDigit($char))
    {
      $lexer->setStateInteger($char);
    }
    else if($char == '(')
    {
      $lexer->pushOperator(new TokenLParen());
    }
    else if($char == '*')
    {
      $lexer->pushOperator(new TokenMul());
    }
    else if($char == ')')
    {
      $lexer->closeParenthesis();
    }
    else if($char == ',')
    {
      $lexer->incrementLastFunctionArity();
    }
    else if($char == '/') {
      $lexer->pushPath(new PathElementRoot());
    }
    else if($char == '.')
    {
      $lexer->setStateDot();
    }
    else if(self::isBlank($char))
    {
    }
    else
    {
      $this->throwError($lexer, $char);
    }
  }

  /**
   * @param Lexer $lexer
   * @codeCoverageIgnore
   */
  public function endOfInput($lexer) {
    // Method left blank on purpose.
    // It is generally legal, parsing-wise, to meet end of input while in Empty state.
    // The grammar of your expression could be wrong, but this is not the place to check it.
    // It is the Lexer's responsibility.
  }

}
