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

class TokenUnarySign extends TokenOperator {
	/**
	 * @var string
	 */
	private $sign;

	/**
	 * @param string $sign
	 */
	public function __construct($sign) {
		parent::__construct(self::PRIORITY_MINUS);
		$this->sign = $sign;
	}

	public function getNumOperands() {
		return 1;
	}

    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		if($this->sign == '-')
			return (- $this->operands[0]->evaluate($context));
		return $this->operands[0]->evaluate($context);
	}
}
