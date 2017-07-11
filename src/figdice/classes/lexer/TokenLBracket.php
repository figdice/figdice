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

class TokenLBracket extends TokenOperator {
	public function __construct() {
		parent::__construct(self::PRIORITY_LEFT_PAREN);
	}

	/**
	 * @return integer
	 */
	public function getNumOperands() {
		return 0;
	}

    /**
     * @codeCoverageIgnore
     * @param Context $context
     * @return mixed
     * @throws \Exception
     */
    public function evaluate(Context $context) {
		throw new \Exception('Abnormal evaluation of left square bracket token.');
	}
}
