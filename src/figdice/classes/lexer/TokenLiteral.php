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

class TokenLiteral extends Token {
	/**
	 * @var mixed
	 */
	public $value;
	/**
	 * @param mixed $value
	 */
	public function __construct($value) {
		parent::__construct();
		$this->value = $value;
	}
	/**
	 * @param Context $context
	 * @return mixed
	 */
	public function evaluate(Context $context) {
		return $this->value;
	}/*
	public function export() {
		$result = 'Tokenliteral::restore(';
		if(is_numeric($this->value)) {
			$result .= $this->value;
		}
		else {
			$result .= '\'' . $this->value . '\'';
		}
		return $result . ')';
	}
	public static function restore($value) {
		return new TokenLiteral($value);
	}*/
}
