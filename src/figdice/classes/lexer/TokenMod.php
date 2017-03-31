<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.1
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
use figdice\classes\Context;

class TokenMod extends TokenBinop {
	/**
	 * @param Lexer $lexer
	 */
	public function __construct() {
		parent::__construct(self::PRIORITY_MUL_DIV );
	}
    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		$opL = $this->operands[0];
		$opR = $this->operands[1];

		$valR = $opR->evaluate($context);
		if($valR == 0) {
			return 0;
		}
		return $opL->evaluate($context) % $valR;
	}
}
