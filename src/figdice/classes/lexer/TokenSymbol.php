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

class TokenSymbol extends Token {
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @param string $name
	 */
	public function __construct($name) {
		parent::__construct();
		$this->name = $name;
	}

    /**
     * @param Context $context
     * @return mixed
     */
    public function evaluate(Context $context) {
		$tokenPath = new TokenPath($this->name);
		return $tokenPath->evaluate($context);
	}
}