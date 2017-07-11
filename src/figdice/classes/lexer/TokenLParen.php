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

class TokenLParen extends TokenOperator {
	public function __construct() {
		parent::__construct(self::PRIORITY_LEFT_PAREN);
	}

	/**
	 * @return integer
	 * @codeCoverageIgnore
	 */
	public function getNumOperands() {
		return 0;
	}

    /**
     * @param Context $context
     *
     * @return mixed
     * @throws \Exception
     */
    public function evaluate(Context $context) {
		throw new \Exception('Abnormal evaluation of left parenthesis token.');
	}
}
