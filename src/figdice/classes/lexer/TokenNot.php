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

class TokenNot extends TokenOperator {
	public function __construct() {
		parent::__construct(self::PRIORITY_MINUS);
	}

	public function getNumOperands() {
		return 1;
	}

    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		$operand = $this->operands[0]->evaluate($context);
		return ! $operand;
	}
}