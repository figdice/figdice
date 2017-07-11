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

class TokenPlusMinus extends TokenOperator {
	public $sign;

	/**
	 * @param string $sign
	 */
	public function __construct($sign) {
		parent::__construct($sign == '-' ? self::PRIORITY_MINUS : self::PRIORITY_PLUS);
		$this->sign = $sign;
	}
	public function getNumOperands() {
		return 2;
	}
    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		$opL = $this->operands[0]->evaluate($context);
		$opR = $this->operands[1]->evaluate($context);
		if($this->sign == '+') {
			if((!is_numeric($opL)) || (!is_numeric($opR))) {
				return $opL . $opR;
			}
			else {
				return $opL + $opR;
			}
		}
		return $opL - $opR;
	}
}
