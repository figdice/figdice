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
use figdice\classes\Context;

class TokenAnd extends TokenBinop {
	public function __construct() {
		parent::__construct(self::PRIORITY_AND_OR);
	}

    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		$opL = $this->operands[0];
		if(true == $opL->evaluate($context)) {
			$opR = $this->operands[1];
			return (true == $opR->evaluate($context));
		}
		return false;
	}
}
