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

abstract class TokenBinop extends TokenOperator {
	/**
	 * @param integer $priority
	 */
	public function __construct($priority)
	{
		parent::__construct($priority);
	}

	public function getNumOperands()
	{
		return 2;
	}
}
