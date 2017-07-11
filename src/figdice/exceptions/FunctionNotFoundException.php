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

namespace figdice\exceptions;

class FunctionNotFoundException extends \Exception {
	private $functionName;
	public function __construct($functionName) {
		parent::__construct("Undefined function: $functionName");
		$this->functionName = $functionName;
	}
}