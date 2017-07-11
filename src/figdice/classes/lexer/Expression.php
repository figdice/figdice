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

class Expression extends Token {
	/**
	 * @var Token
	 */
	private $root;

	public function __construct(Token $token) {
		parent::__construct();
		$this->root = $token;
	}

    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		return $this->root->evaluate($context);
	}
}