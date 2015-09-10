<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.2
 * @package FigDice
 *
 * This file is part of FigDice.
 * http://figdice.org/
 */

namespace figdice\classes\functions;

use \figdice\FigFunction;
use \figdice\classes\ViewElementTag;
use \figdice\exceptions\FunctionCallException;

class Function_htmlentities implements FigFunction {
	public function __construct() {
	}

	/**
	 * Function's arguments:
	 *  string to escape
	 * 
	 * @param ViewElement $viewElement
	 * @param integer $arity
	 * @param array $arguments
	 */
	public function evaluate(ViewElementTag $viewElement, $arity, $arguments) {
		if($arity != 1) {
			throw new FunctionCallException(
				'htmlentities',
				'Expects exactly 1 argument.',
				$viewElement->getCurrentFile()->getFilename(), 
				$viewElement->getLineNumber()
			);
		}

		$string = $arguments[0];
		return htmlentities($string);
	}
}
