<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
 * @package FigDice
 *
 * This file is part of FigDice.
 */

namespace figdice;

interface Filter {

	/**
	 * Operates the transform on the input string,
	 * returns the filtered output.
	 *
	 * @param string $buffer
	 * @return string
	 */
	public function transform($buffer);
}