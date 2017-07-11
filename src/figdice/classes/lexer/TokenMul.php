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

class TokenMul extends TokenOperator {
	public function __construct() {
		parent::__construct(self::PRIORITY_MUL_DIV);
	}
	public function getNumOperands() {
		return 2;
	}

    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		return $this->operands[0]->evaluate($context) * $this->operands[1]->evaluate($context);
	}
}