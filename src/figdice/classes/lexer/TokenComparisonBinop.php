<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.2
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
use \figdice\classes\ViewElementTag;
use \figdice\exceptions\LexerArrayToStringConversionException;


class TokenComparisonBinop extends TokenBinop {
	public $comparator;

	/**
	 * @param string $comparator
	 */
	public function __construct($comparator) {
		parent::__construct(self::PRIORITY_COMPARATOR);
		$this->comparator = $comparator;
	}
    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		$opL = $this->operands[0]->evaluate($context);
		$opR = $this->operands[1]->evaluate($context);

		if ($opL instanceof \DOMNode) $opL = $opL->nodeValue;
		if ($opR instanceof \DOMNode) $opR = $opR->nodeValue;

		switch($this->comparator) {
			case 'gt' : return ($opL >  $opR);
			case 'gte': return ($opL >= $opR);
			case 'lt' : return ($opL <  $opR);
			case 'lte': return ($opL <= $opR);

			case '==' :
				//First: let's check for empty variables:
				if(
						(empty($opL) && ! empty($opR)) ||
						(empty($opR) && ! empty($opL))
					) {
					return false;
				}

				//If one of the operands is a string,
				//we must do a string comparison.
				if(is_string($opL) || is_string($opR)) {
					if(is_array($opL) || is_array($opR)) {
						//But if the other is an array, we cannot convert Array to String
						//to perform the comparison, so we throw an error.
						throw new LexerArrayToStringConversionException();
					}
					else {
						return (0 === strcmp($opL, $opR));
					}
				}
				else {
					return $opL == $opR;
				}

			case '!=' :
				if(is_float($opL)) {
					return ($opL . '' != $opR . '');
				}
				else {
					return ($opL != $opR);
				}

      // the "default:" case is not a possible branch.
      // I am required to write a default for the switch statement,
      // but it can never happen because the TokenComparisonBinop is only valid
      // for the above operators.
      // @codeCoverageIgnoreStart
			default: return false;
		}
    // @codeCoverageIgnoreEnd
    // I place this end annotation after the } of the switch, otherwise the } is
    // considered not covered!
    // I hate these hacks, but so far this is all what PHPUnit Coverage as to offer.
	}
}
