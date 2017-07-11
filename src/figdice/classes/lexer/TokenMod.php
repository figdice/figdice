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
use figdice\classes\Context;

class TokenMod extends TokenBinop {
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
