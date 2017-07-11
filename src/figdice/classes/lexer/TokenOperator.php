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

abstract class TokenOperator extends Token {
	const PRIORITY_LEFT_PAREN =  0;
	const PRIORITY_FUNCTION 	= 10;
	const PRIORITY_AND_OR			= 20;
	const PRIORITY_COMPARATOR = 30;
	const PRIORITY_PLUS 			= 40;
	const PRIORITY_MINUS 			= 50;
	const PRIORITY_MUL_DIV 		= 60;

	/**
	 * @var integer
	 */
	protected $priority;

	/**
	 * @var Token[]
	 */
	protected $operands;

	/**
	 * @param integer $priority
	 */
	public function __construct($priority)
	{
		parent::__construct();
		$this->priority = $priority;
	}

	/**
	 * @return integer
	 */
	abstract public function getNumOperands();

	public function setOperands(array $operands) {
		$this->operands = $operands;
	}

	/**
	 * @return integer
	 */
	public function getPriority()
	{
		return $this->priority;
	}
}
